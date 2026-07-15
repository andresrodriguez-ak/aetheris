# ══════════════════════════════════════════════════════════════════════════
# AETHERIS — INTEGRITY_CHECK.PY
# Chequea anime/manga/novela: contenido sin género, imágenes rotas,
# géneros duplicados, y episodios huérfanos/sin embed (solo anime).
# ══════════════════════════════════════════════════════════════════════════
import argparse
import os
import sys
from pathlib import Path

from dotenv import load_dotenv

from common.db import get_connection

load_dotenv()

PROJECT_ROOT = os.getenv("PROJECT_ROOT")
if not PROJECT_ROOT:
    sys.exit("Definí PROJECT_ROOT en tu .env (ruta absoluta a la carpeta aetheris/).")

PUBLIC_DIR = Path(PROJECT_ROOT) / "public"

CONTENT_TYPES = {
    "anime": {
        "table": "animes",
        "genero_table": "anime_generos",
        "genero_fk": "anime_id",
        "has_portada": True,
        "label": "Anime",
    },
    "manga": {
        "table": "mangas",
        "genero_table": "manga_generos",
        "genero_fk": "manga_id",
        "has_portada": True,
        "label": "Manga",
    },
    "novela": {
        "table": "novelas",
        "genero_table": "novela_generos",
        "genero_fk": "novela_id",
        "has_portada": False,
        "label": "Novela",
    },
}


def check_image_exists(rel_path):
    if not rel_path:
        return None
    return (PUBLIC_DIR / rel_path).is_file()


def contenido_sin_genero(cur, cfg):
    cur.execute(
        f"""
        SELECT c.id, c.nombre
        FROM {cfg['table']} c
        LEFT JOIN {cfg['genero_table']} cg ON cg.{cfg['genero_fk']} = c.id
        WHERE cg.genero_id IS NULL
        """
    )
    return cur.fetchall()


def contenido_imagenes_faltantes(cur, cfg):
    campos = "id, nombre, imagen" + (", portada" if cfg["has_portada"] else "")
    cur.execute(f"SELECT {campos} FROM {cfg['table']}")
    faltantes = []
    campos_a_chequear = ["imagen"] + (["portada"] if cfg["has_portada"] else [])
    for row in cur.fetchall():
        for campo in campos_a_chequear:
            if check_image_exists(row[campo]) is False:
                faltantes.append((row["id"], row["nombre"], campo, row[campo]))
    return faltantes


def episodios_huerfanos(cur):
    cur.execute(
        """
        SELECT e.id, e.id_anime, e.numero_episodio
        FROM episodios e
        LEFT JOIN animes a ON a.id = e.id_anime
        WHERE a.id IS NULL
        """
    )
    return cur.fetchall()


def episodios_sin_embed(cur):
    cur.execute(
        """
        SELECT id, id_anime, numero_episodio
        FROM episodios
        WHERE embed_code IS NULL OR TRIM(embed_code) = ''
        """
    )
    return cur.fetchall()


def generos_duplicados(cur):
    cur.execute(
        """
        SELECT LOWER(TRIM(nombre)) AS nombre_norm, GROUP_CONCAT(id) AS ids, COUNT(*) AS total
        FROM generos
        GROUP BY nombre_norm
        HAVING total > 1
        """
    )
    return cur.fetchall()


def imprimir_seccion(titulo, filas, formatear):
    print(f"\n{titulo} ({len(filas)})")
    print("-" * len(titulo))
    if not filas:
        print("  Sin problemas.")
        return
    for fila in filas:
        print(f"  {formatear(fila)}")


def revisar_tipo(cur, tipo):
    cfg = CONTENT_TYPES[tipo]
    print(f"\n\n{'#' * 3} {cfg['label']} {'#' * 3}")

    sin_genero = contenido_sin_genero(cur, cfg)
    imprimir_seccion(
        f"{cfg['label']} sin género asignado", sin_genero, lambda r: f"#{r['id']} — {r['nombre']}"
    )

    faltantes = contenido_imagenes_faltantes(cur, cfg)
    imprimir_seccion(
        "Imágenes referenciadas que no existen en disco",
        faltantes,
        lambda r: f"#{r[0]} — {r[1]} — campo '{r[2]}' → {r[3]}",
    )

    return sin_genero, faltantes


def main():
    parser = argparse.ArgumentParser(description="Chequeo de integridad de datos de Aetheris")
    parser.add_argument("--tipo", choices=["anime", "manga", "novela", "todos"], default="todos")
    parser.add_argument("--fix-orphans", action="store_true")
    args = parser.parse_args()

    conn = get_connection()
    cur = conn.cursor()

    print("Aetheris — Chequeo de integridad de datos")
    print("=" * 42)

    tipos = ["anime", "manga", "novela"] if args.tipo == "todos" else [args.tipo]

    resumen = {}
    for tipo in tipos:
        sin_genero, faltantes = revisar_tipo(cur, tipo)
        resumen[tipo] = (len(sin_genero), len(faltantes))

    huerfanos, sin_embed = [], []
    if "anime" in tipos:
        huerfanos = episodios_huerfanos(cur)
        imprimir_seccion(
            "Episodios huérfanos (id_anime inexistente)",
            huerfanos,
            lambda r: f"episodio #{r['id']} — ep. {r['numero_episodio']} — id_anime={r['id_anime']} (no existe)",
        )

        sin_embed = episodios_sin_embed(cur)
        imprimir_seccion(
            "Episodios sin embed_code",
            sin_embed,
            lambda r: f"episodio #{r['id']} — anime #{r['id_anime']} — ep. {r['numero_episodio']}",
        )

    dup_generos = generos_duplicados(cur)
    imprimir_seccion(
        "Géneros duplicados (tabla compartida)",
        dup_generos,
        lambda r: f"'{r['nombre_norm']}' — ids: {r['ids']}",
    )

    print("\n" + "=" * 42)
    print("Resumen:")
    for tipo, (n_sin_genero, n_faltantes) in resumen.items():
        print(
            f"  {CONTENT_TYPES[tipo]['label']}: {n_sin_genero} sin género · {n_faltantes} imágenes faltantes"
        )
    if "anime" in tipos:
        print(f"  Anime: {len(huerfanos)} episodios huérfanos · {len(sin_embed)} sin embed")
    print(f"  Géneros duplicados: {len(dup_generos)}")

    if args.fix_orphans and huerfanos:
        confirm = input(
            f"\n¿Borrar los {len(huerfanos)} episodios huérfanos? (escribí 'si' para confirmar): "
        )
        if confirm.strip().lower() == "si":
            ids = [str(r["id"]) for r in huerfanos]
            cur.execute(f"DELETE FROM episodios WHERE id IN ({','.join(ids)})")
            conn.commit()
            print(f"Se borraron {cur.rowcount} episodios huérfanos.")
        else:
            print("Cancelado, no se borró nada.")

    cur.close()
    conn.close()


if __name__ == "__main__":
    main()
