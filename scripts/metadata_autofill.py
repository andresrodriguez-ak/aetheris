# ══════════════════════════════════════════════════════════════════════════
# AETHERIS — METADATA_AUTOFILL.PY
# Busca anime/manga/novela en Jikan y arma la fila lista para insertar,
# con la imagen ya convertida a WEBP.
# ══════════════════════════════════════════════════════════════════════════
import argparse
import io
import os
import sys
import time
import uuid
from pathlib import Path

import requests
from dotenv import load_dotenv
from PIL import Image

from common.db import get_connection

load_dotenv()

JIKAN_BASE = "https://api.jikan.moe/v4"

ESTADO_MAP_ANIME = {
    "Currently Airing": "En emisión",
    "Finished Airing": "Finalizado",
    "Not yet aired": "Próximamente",
}

ESTADO_MAP_MANGA = {
    "Publishing": "En emisión",
    "Finished": "Finalizado",
    "On Hiatus": "Finalizado",
    "Discontinued": "Finalizado",
    "Not yet published": "Próximamente",
}

CONTENT_TYPES = {
    "anime": {
        "table": "animes",
        "genero_table": "anime_generos",
        "genero_fk": "anime_id",
        "upload_dir": "uploads/animes",
        "jikan_path": "anime",
        "jikan_type_param": None,
        "has_portada": True,
        "has_estado": True,
        "fecha_field": "fecha_emision",
        "fecha_jikan_key": "aired",
        "estado_map": ESTADO_MAP_ANIME,
    },
    "manga": {
        "table": "mangas",
        "genero_table": "manga_generos",
        "genero_fk": "manga_id",
        "upload_dir": "uploads/mangas",
        "jikan_path": "manga",
        "jikan_type_param": "manga",
        "has_portada": True,
        "has_estado": True,
        "fecha_field": "fecha_publicacion",
        "fecha_jikan_key": "published",
        "estado_map": ESTADO_MAP_MANGA,
    },
    "novela": {
        "table": "novelas",
        "genero_table": "novela_generos",
        "genero_fk": "novela_id",
        "upload_dir": "uploads/novelas",
        "jikan_path": "manga",
        "jikan_type_param": "lightnovel",
        "has_portada": False,
        "has_estado": False,
        "fecha_field": None,
        "fecha_jikan_key": "published",
        "estado_map": ESTADO_MAP_MANGA,
    },
}

PROJECT_ROOT = os.getenv("PROJECT_ROOT")
if not PROJECT_ROOT:
    sys.exit("Definí PROJECT_ROOT en tu .env (ruta absoluta a la carpeta aetheris/).")


def buscar(cfg, query):
    params = {"q": query, "limit": 8}
    if cfg["jikan_type_param"]:
        params["type"] = cfg["jikan_type_param"]
    try:
        resp = requests.get(f"{JIKAN_BASE}/{cfg['jikan_path']}", params=params, timeout=15)
        resp.raise_for_status()
    except requests.RequestException as e:
        sys.exit(f"No se pudo consultar Jikan: {e}")
    return resp.json().get("data", [])


def elegir_resultado(resultados):
    if not resultados:
        sys.exit("No se encontraron resultados.")
    print("\nResultados:")
    for i, r in enumerate(resultados, start=1):
        anio = r.get("year") or "?"
        print(f"  [{i}] {r['title']} ({anio}) — {r.get('type', '?')}")
    while True:
        eleccion = input("\nElegí un número (o 'q' para salir): ").strip()
        if eleccion.lower() == "q":
            sys.exit(0)
        if eleccion.isdigit() and 1 <= int(eleccion) <= len(resultados):
            return resultados[int(eleccion) - 1]
        print("Opción inválida.")


def descargar_como_webp(url, upload_dir):
    if not url:
        return ""
    try:
        resp = requests.get(url, timeout=15)
        resp.raise_for_status()
    except requests.RequestException as e:
        print(f"No se pudo descargar la imagen ({e}), se sigue sin imagen.")
        return ""

    imagen = Image.open(io.BytesIO(resp.content)).convert("RGB")
    destino_abs = Path(PROJECT_ROOT) / "public" / upload_dir
    destino_abs.mkdir(parents=True, exist_ok=True)
    nombre_archivo = f"{uuid.uuid4().hex}.webp"
    ruta_abs = destino_abs / nombre_archivo
    imagen.save(ruta_abs, "WEBP", quality=85)

    return f"{upload_dir}/{nombre_archivo}"


def matchear_generos(cur, nombres_generos):
    encontrados, faltantes = [], []
    for nombre in nombres_generos:
        cur.execute("SELECT id FROM generos WHERE LOWER(nombre) = LOWER(%s)", (nombre,))
        fila = cur.fetchone()
        if fila:
            encontrados.append(fila["id"])
        else:
            faltantes.append(nombre)
    return encontrados, faltantes


def main():
    parser = argparse.ArgumentParser(description="Auto-completado de metadata (Jikan API)")
    parser.add_argument("tipo", choices=list(CONTENT_TYPES.keys()))
    parser.add_argument("titulo")
    parser.add_argument("--commit", action="store_true")
    args = parser.parse_args()

    cfg = CONTENT_TYPES[args.tipo]

    resultados = buscar(cfg, args.titulo)
    elegido = elegir_resultado(resultados)

    time.sleep(0.4)
    detalle = requests.get(
        f"{JIKAN_BASE}/{cfg['jikan_path']}/{elegido['mal_id']}/full", timeout=15
    ).json()["data"]

    nombre = detalle["title"]
    descripcion = (detalle.get("synopsis") or "").strip()
    generos_nombres = [g["name"] for g in detalle.get("genres", [])]
    imagen_url = (detalle.get("images") or {}).get("jpg", {}).get("large_image_url")

    estado = None
    if cfg["has_estado"]:
        estado = cfg["estado_map"].get(detalle.get("status"), "En emisión")

    fecha = None
    if cfg["fecha_field"]:
        bloque_fecha = detalle.get(cfg["fecha_jikan_key"]) or {}
        fecha_raw = bloque_fecha.get("from")
        fecha = fecha_raw[:10] if fecha_raw else None

    print(f"\nDescargando y convirtiendo imagen a WEBP en {cfg['upload_dir']}/...")
    imagen_rel = descargar_como_webp(imagen_url, cfg["upload_dir"])

    conn = get_connection()
    cur = conn.cursor()
    generos_ids, generos_faltantes = matchear_generos(cur, generos_nombres)

    print("\n" + "=" * 50)
    print(f"Tipo:          {args.tipo}")
    print(f"Nombre:        {nombre}")
    if cfg["has_estado"]:
        print(f"Estado:        {estado}")
    if cfg["fecha_field"]:
        print(f"Fecha:         {fecha or '(sin dato)'}")
    print(f"Imagen:        {imagen_rel or '(no descargada)'}")
    print(f"Géneros OK:    {generos_nombres}")
    if generos_faltantes:
        print(f"Géneros SIN match en tu BD (creálos en generos primero): {generos_faltantes}")
    print(f"Sinopsis:      {descripcion[:200]}{'...' if len(descripcion) > 200 else ''}")
    print("=" * 50)

    if args.tipo == "novela":
        print("Nota: la tabla 'novelas' no tiene portada/estado/fecha, así que no se guardan.")
    elif cfg["has_portada"]:
        print("Nota: imagen y portada quedan iguales, Jikan no da un banner separado.")

    if not args.commit:
        print("\n(Modo simulación, no se guardó nada. Corré con --commit para insertar.)")
        cur.close()
        conn.close()
        return

    columnas = ["nombre", "descripcion", "imagen"]
    valores = [nombre, descripcion, imagen_rel]

    if cfg["has_portada"]:
        columnas.append("portada")
        valores.append(imagen_rel)
    if cfg["has_estado"]:
        columnas.append("estado")
        valores.append(estado)
    if cfg["fecha_field"]:
        columnas.append(cfg["fecha_field"])
        valores.append(fecha)

    placeholders = ", ".join(["%s"] * len(columnas))
    cur.execute(
        f"INSERT INTO {cfg['table']} ({', '.join(columnas)}) VALUES ({placeholders})",
        valores,
    )
    nuevo_id = cur.lastrowid

    for genero_id in generos_ids:
        cur.execute(
            f"INSERT INTO {cfg['genero_table']} ({cfg['genero_fk']}, genero_id) VALUES (%s, %s)",
            (nuevo_id, genero_id),
        )

    conn.commit()
    print(f"\nInsertado correctamente en '{cfg['table']}'. id = {nuevo_id}")
    if generos_faltantes:
        print("Recordá agregar manualmente los géneros que no matchearon.")

    cur.close()
    conn.close()


if __name__ == "__main__":
    main()
