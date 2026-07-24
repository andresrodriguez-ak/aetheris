# Aetheris

> Plataforma web de entretenimiento japonés — Anime, Manga y Novelas Ligeras.

Construida con PHP + MySQLi, arquitectura limpia separada en capas: presentación, lógica y datos.

---

## Estructura del proyecto

```
aetheris/
├── config/
│   ├── app_config.php          # Configuración general (BASE_URL, etc.)
│   └── db_config.php           # Conexión a BD 
├── public/                     # Única carpeta accesible desde el navegador
│   ├── css/                    # Estilos separados por sección
│   │   ├── global.css          # Variables, reset, header, menú, footer
│   │   ├── animations.css      # Carrusel 3D, partículas
│   │   ├── home.css            # Portada general
│   │   ├── anime.css           # Catálogo y detalle de anime
│   │   ├── manga.css           # Catálogo, detalle y lector de manga
│   │   ├── novela.css          # Catálogo, detalle y lector de novela
│   │   ├── directorio.css      # Directorio general (filtros + grid multi-tipo)
│   │   ├── perfil.css          # Perfil de usuario (favoritos, seguimiento, progreso)
│   │   ├── busqueda.css        # Página de resultados de búsqueda (filtros, tarjetas de resultado)
│   │   ├── auth.css            # Login / signup
│   │   └── action-buttons.css  # Botón favorito + dropdown de estado (anime/manga/novela)
│   ├── js/
│   │   ├── main.js             # Menú desplegable
│   │   ├── animations.js       # Partículas, estrellas de tarjetas, carrusel 3D orbital y easter egg de sakura
│   │   ├── search.js           # Buscador del header + resaltado y filtros en la página de resultados
│   │   ├── ajax-actions.js     # fetch() de favoritos, progreso, seguimiento
│   │   ├── action-buttons.js   # Favorito + dropdown de estado (compartido anime/manga/novela)
│   │   ├── anime-home.js       # Filtros y paginación de catálogo (anime)
│   │   ├── manga-home.js       # Filtros y paginación de catálogo (manga)
│   │   ├── anime-detalle.js    # Progreso y episodios vistos (anime)
│   │   ├── manga-detalle.js    # Progreso y capítulos leídos (manga)
│   │   ├── manga-lector.js     # Visor de PDF (pantalla completa, navegación)
│   │   ├── novela-detalle.js   # Progreso y volúmenes leídos (novela — por volúmenes)
│   │   ├── novela-lector.js    # Visor de PDF (pantalla completa, navegación entre volúmenes)
│   │   ├── directorio.js       # Filtros combinados y paginación (anime + manga + novela)
│   │   └── perfil.js           # Modales de editar/eliminar cuenta, filtro y file input custom
│   ├── uploads/                # Imágenes y videos (no versionados)
│   │   └── defaults/           # Placeholders (avatar y contenido) para imágenes rotas
│   └── *.php                   # Vistas públicas
└── src/                        # Motor interno
    ├── includes/
    │   ├── header.php          # session_start, DB, menú global, inyección de CSS/acento por página
    │   ├── footer.php          # window.BASE_URL, scripts JS y cierre HTML
    │   └── components/
    │       └── action_buttons.php  # Botón favorito + dropdown de estado (compartido anime/manga/novela)
    ├── actions/
    │   ├── auth/            # logout, etc.
    │   └── ajax/            # Endpoints JSON para fetch() (ajax_catalogo_animes.php, ajax_catalogo_mangas.php,
    │                        # ajax_filtrar_directorio.php, acciones-contenido.php, etc.)
    └── admin/               # Panel de administración
        ├── includes/
        │   ├── header.php          # Header propio de admin (reusa global.css + $accent_color de la sección)
        │   ├── footer.php          # Carga admin.js + $admin_js extra por página
        │   └── helpers.php         # guardarImagenComoWebp() compartido por los formularios de carga/edición
        ├── css/
        │   ├── admin.css              # Dashboard: tarjetas de sección, menú, alertas
        │   ├── lista_contenido.css    # Editor de episodios/capítulos/volúmenes (select con buscador)
        │   ├── subir_contenido.css    # Formularios de carga (subir_anime, subir_manga, etc.)
        │   └── editar_contenido.css   # Pestañas y estado vacío del editor unificado
        ├── js/
        │   ├── admin.js               # Menú + buscador del header de admin
        │   ├── lista_contenido.js     # Desplegable + filtro en editores de contenido
        │   ├── subir_contenido.js     # Carga de imagen con preview integrada + select con buscador
        │   └── editar_contenido.js    # Pestañas + selector dinámico por AJAX del editor unificado
        ├── subir_contenido.php     # Dashboard con accesos a cada sección
        ├── subir_anime.php         # Alta de series de anime (imágenes se convierten a WEBP)
        ├── subir_episodio.php      # Alta de episodios (embed_code)
        ├── lista_episodios.php     # Edición de embeds de episodios ya cargados
        ├── subir_manga.php         # Alta de series de manga (imágenes se convierten a WEBP)
        ├── subir_capitulo.php      # Alta de capítulos de manga (enlace de Google Drive)
        ├── lista_capitulos.php     # Edición de enlaces de Drive de capítulos ya cargados
        ├── subir_novela.php        # Alta de novelas ligeras (imágenes se convierten a WEBP)
        ├── subir_volumen.php       # Alta de volúmenes de novela (enlace de Google Drive)
        ├── lista_volumenes.php     # Edición de enlaces de Drive de volúmenes ya cargados
        └── editar_contenido.php    # Edición de anime/manga/novela ya cargados (pestañas + AJAX)
```

## Características técnicas

- **Variables CSS dinámicas** (`--accent-current`) que cambian el color de acento según la sección activa sin duplicar CSS.
- **Sistema de CSS/JS por página**: cada vista define `$accent_color` y `$page_css` antes de incluir `header.php`, que inyecta automáticamente `global.css` + los CSS propios de esa sección + la variable de acento correspondiente.
- **Patrón de vista limpia**: cada `.php` ejecuta sus consultas SQL arriba (y su propio manejo de AJAX vía POST antes de imprimir HTML) y pinta HTML puro abajo.
- **AJAX aislado**: los endpoints que devuelven JSON puro para catálogos filtrados viven en `src/actions/ajax/`. Las acciones de usuario sobre contenido (favorito, estado de seguimiento) están centralizadas en `src/actions/ajax/acciones-contenido.php`, compartido por anime/manga/novela; las acciones específicas de cada tipo (episodio/capítulo/volumen visto) se manejan directo en la vista de detalle antes del `header.php`.
- **Favorito + estado de seguimiento unificado**: componente compartido (`action_buttons.php` + `action-buttons.css` + `action-buttons.js`) usado por las tres páginas de detalle. Reemplaza los botones sueltos de Seguir/Ver más tarde por un dropdown de 5 estados (Viendo, Por ver, Completado, Pausado, Descartado) sobre la columna `favoritos.estado_seguimiento`.
- **Animaciones**: carrusel 3D con perspectiva CSS, partículas en canvas.
- **Protección CSRF**: formularios que modifican o eliminan datos de cuenta llevan un token por sesión (`$_SESSION['csrf_token']`), validado con `hash_equals()` antes de procesar cualquier POST.
- **Fallback de imágenes**: si una imagen de contenido o de avatar no carga, se reemplaza por un placeholder fijo en `uploads/defaults/` (con `this.onerror=null` para evitar loops si el placeholder tampoco existe).
- **Conversión automática a WEBP**: las imágenes subidas desde el panel de administración (JPEG, PNG o WEBP) se procesan con GD y se guardan siempre como `.webp`, sin importar el formato original.
- **Edición unificada de contenido**: `editar_contenido.php` permite editar anime, manga y novela ya cargados desde una sola pantalla con pestañas, cargando la lista y el detalle de cada ítem vía AJAX (`?ajax=get_list` / `?ajax=get_item`) en vez de una página por tipo.

## Instalación local

```bash
# 1. Clonar el repositorio
git clone https://github.com/andresrodriguez-ak/aetheris.git

# 2. Crear el archivo de configuración de BD (NO está en el repo)
# Crear config/db_config.php con tus credenciales, siguiendo el mismo
# formato de config/app_config.php (require_once + $servername, $username, etc.)

# 3. Revisar config/app_config.php
# BASE_URL debe coincidir con la ruta real donde sirves el proyecto
# (por defecto: '/aetheris/')

# 4. Importar la base de datos
mysql -u root -p documentos < aetheris.sql

# 5. Apuntar el servidor web a la carpeta raíz del proyecto (aetheris/, NO public/)
# Apache: DocumentRoot → .../aetheris/
# El .htaccess en la raíz reescribe todo hacia public/ automáticamente,
# así que las URLs se ven sin "/public/" (ej: http://localhost/aetheris/anime-home.php)
```

## Sistema de colores

| Sección  | Variable CSS         | Color      | Variable "light"      | Color claro |
|----------|----------------------|------------|------------------------|-------------|
| General  | `--primary-blue`     | `#4a8eff`  | —                       | —           |
| Anime    | `--anime-color`      | `#b980f5`  | `--anime-light`        | `#dcb3ff`   |
| Manga    | `--manga-color`      | `#680015`  | `--manga-light`        | `#b3324a`   |
| Novela   | `--novela-color`     | `#00ced1`  | `--novela-light`       | `#5ee8e8`   |

El color activo se inyecta en cada vista según el `$accent_color` que defina la página (`anime`, `manga`, `novela` o `general`), en tres variables:
- `--accent-current`: color de acento de la sección (bordes, iconos activos).
- `--accent-light`: variante clara del mismo color, para texto/iconos que necesitan más contraste sobre fondo oscuro.
- `--card-bg-current`: fondo de tarjeta propio de la sección (ej. `--manga-card-bg`), usado por componentes compartidos como `action_buttons.php` para no heredar un fondo pensado para otra sección.





---

**Stack:** PHP · MySQLi · CSS Variables · Canvas API