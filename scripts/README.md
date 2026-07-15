# Aetheris — Scripts de Python

Herramientas de mantenimiento que corren aparte del sitio (no forman
parte del flujo web PHP). Se conectan a la misma base de datos MySQL
por variables de entorno.

## Instalación

```bash
cd scripts
python -m venv venv
source venv/bin/activate        # Windows: venv\Scripts\activate
pip install -r requirements.txt
cp .env.example .env
# editá .env con tus credenciales de BD y la ruta absoluta al proyecto
```

## integrity_check.py

Revisa la base de datos en busca de inconsistencias. Cubre **anime, manga
y novela**, adaptado al esquema real de cada tabla (la tabla `novelas`
hoy no tiene `portada`/`estado`/`fecha`, así que esos chequeos se
omiten para novela automáticamente):

- Contenido sin género asignado
- Imágenes referenciadas en la BD que ya no existen en disco
- Géneros duplicados (mismo nombre, distinto id — tabla compartida)
- Solo anime por ahora: episodios huérfanos (apuntan a un anime que ya
  no existe) y episodios sin `embed_code`

```bash
python integrity_check.py                # revisa anime + manga + novela
python integrity_check.py --tipo manga    # solo manga
python integrity_check.py --tipo novela   # solo novela

# para borrar los episodios huérfanos de anime que encuentre (pide confirmación)
python integrity_check.py --fix-orphans
```

Cuando tengas las tablas `capitulos`/`volumenes` (manga/novela), avisame
para sumarles el mismo chequeo de huérfanos/sin-embed que hoy solo
corre para `episodios`.

## metadata_autofill.py

Busca un título en [Jikan](https://jikan.moe/) (API pública de
MyAnimeList, sin necesidad de API key) para **anime, manga o novela
ligera**, te deja elegir el resultado correcto entre varios, y arma la
fila lista para la tabla correspondiente: sinopsis, imagen ya
descargada y convertida a WEBP (mismo criterio que usan
`subir_anime.php`/`subir_manga.php`), y estado/fecha cuando la tabla
los tiene. Los géneros se intentan matchear contra tu tabla `generos`
local por nombre (compartida entre los tres tipos).

```bash
# modo simulación: busca, muestra todo, no toca la base de datos
python metadata_autofill.py anime "Shingeki no Kyojin"
python metadata_autofill.py manga "Berserk"
python metadata_autofill.py novela "Re:Zero"

# inserta directo en la base de datos
python metadata_autofill.py manga "One Piece" --commit
```

Notas:

- Si un género de Jikan no matchea con ningún nombre de tu tabla
  `generos`, el script avisa y lo deja afuera — no crea géneros nuevos
  solo, para que decidas vos el nombre exacto que querés usar.
- Para `novela`, la tabla `novelas` actual solo tiene la columna
  `imagen` (no `portada`/`estado`/`fecha`), así que el script inserta
  únicamente lo que la tabla soporta hoy. Si más adelante agregás esas
  columnas, avisame para actualizar el script.
- Para `anime`/`manga`, Jikan no separa "miniatura" de "portada/banner",
  así que ambas quedan con la misma imagen. Podés reemplazar la
  portada a mano después desde el formulario de subida.
- Jikan pide no pasarse de ~3 requests por segundo; el script ya
  respeta eso con una pequeña pausa entre llamadas.
