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
│   │   └── auth.css            # Login / signup
│   ├── js/
│   │   ├── main.js             # Menú y búsqueda globales
│   │   ├── animations.js       # Partículas, carrusel
│   │   ├── search.js           # Buscador
│   │   ├── ajax-actions.js     # fetch() de favoritos, progreso, seguimiento
│   │   ├── anime-home.js       # Filtros y paginación de catálogo (anime)
│   │   ├── manga-home.js       # Filtros y paginación de catálogo (manga)
│   │   ├── anime-detalle.js    # Favoritos, seguir, progreso (anime)
│   │   ├── manga-detalle.js    # Favoritos, seguir, progreso (manga)
│   │   ├── manga-lector.js     # Visor de PDF (pantalla completa, navegación)
│   │   ├── novela-detalle.js   # Favoritos, seguir, progreso (novela — por volúmenes)
│   │   ├── novela-lector.js    # Visor de PDF (pantalla completa, navegación entre volúmenes)
│   │   ├── directorio.js       # Filtros combinados y paginación (anime + manga + novela)
│   │   └── perfil.js           # Modales de editar/eliminar cuenta, filtro y file input custom
│   ├── uploads/                # Imágenes y videos (no versionados)
│   │   └── defaults/           # Placeholders (avatar y contenido) para imágenes rotas
│   └── *.php                   # Vistas públicas
└── src/                        # Motor interno
    ├── includes/
    │   ├── header.php          # session_start, DB, menú global, inyección de CSS/acento por página
    │   └── footer.php          # window.BASE_URL, scripts JS y cierre HTML
    ├── actions/
    │   ├── auth/            # logout, etc.
    │   └── ajax/            # Endpoints JSON para fetch() (ajax_catalogo_animes.php, ajax_catalogo_mangas.php, ajax_filtrar_directorio.php, etc.)
    └── admin/               # Panel de administración
```

## Características técnicas

- **Variables CSS dinámicas** (`--accent-current`) que cambian el color de acento según la sección activa sin duplicar CSS.
- **Sistema de CSS/JS por página**: cada vista define `$accent_color` y `$page_css` antes de incluir `header.php`, que inyecta automáticamente `global.css` + los CSS propios de esa sección + la variable de acento correspondiente.
- **Patrón de vista limpia**: cada `.php` ejecuta sus consultas SQL arriba (y su propio manejo de AJAX vía POST antes de imprimir HTML) y pinta HTML puro abajo.
- **AJAX aislado**: los endpoints que devuelven JSON puro para catálogos filtrados viven en `src/actions/ajax/`; las acciones de usuario (favorito, seguir, progreso) se manejan directo en la vista de detalle antes del `header.php`.
- **Animaciones**: carrusel 3D con perspectiva CSS, partículas en canvas.
- **Protección CSRF**: formularios que modifican o eliminan datos de cuenta llevan un token por sesión (`$_SESSION['csrf_token']`), validado con `hash_equals()` antes de procesar cualquier POST.
- **Fallback de imágenes**: si una imagen de contenido o de avatar no carga, se reemplaza por un placeholder fijo en `uploads/defaults/` (con `this.onerror=null` para evitar loops si el placeholder tampoco existe).

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

| Sección  | Variable CSS         | Color      |
|----------|----------------------|------------|
| General  | `--primary-blue`     | `#4a8eff`  |
| Anime    | `--anime-color`      | `#9d4edd`  |
| Manga    | `--manga-color`      | `#680015`  |
| Novela   | `--novela-color`     | `#00ced1`  |

El color activo se inyecta en cada vista como `--accent-current` en el `<head>`, según el `$accent_color` que defina la página (`anime`, `manga`, `novela` o `general`).



---

**Stack:** PHP · MySQLi · CSS Variables · Canvas API