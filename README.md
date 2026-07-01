# Aetheris 🎌

> Plataforma web de entretenimiento japonés — Anime, Manga y Novelas Ligeras.

Construida con PHP + MySQLi, arquitectura limpia separada en capas: presentación, lógica y datos.

---

##  Estructura del proyecto

```
aetheris/
├── config/               # Conexión a BD (excluida del repo vía .gitignore)
├── public/               # Única carpeta accesible desde el navegador
│   ├── css/              # Estilos separados por responsabilidad
│   │   ├── global.css    # Variables, reset, header, menú, footer
│   │   ├── animations.css # Carrusel 3D, partículas, sakura
│   │   ├── light-mode.css
│   │   └── dark-mode.css
│   ├── js/
│   │   ├── main.js       # Menú, búsqueda, barra de progreso
│   │   ├── theme.js      # Toggle claro/oscuro con video de transición
│   │   ├── animations.js # Partículas, estrellas, sakura, carrusel
│   │   └── ajax-actions.js # fetch() de favoritos, progreso, seguimiento
│   ├── uploads/          # Imágenes y videos (no versionados)
│   └── *.php             # Vistas públicas
└── src/                  # Motor interno (no accesible desde el navegador)
    ├── includes/
    │   ├── header.php    # session_start, DB, menú global
    │   └── footer.php    # Scripts JS y cierre HTML
    ├── actions/
    │   ├── auth/         # logout, etc.
    │   └── ajax/         # Endpoints JSON para fetch()
    └── admin/            # Panel de administración protegido
```

##  Características técnicas

- **Variables CSS dinámicas** (`--accent-current`) que cambian el color de acento según la sección activa sin duplicar CSS.
- **Patrón de vista limpia**: cada `.php` ejecuta sus consultas SQL arriba y pinta HTML puro abajo.
- **AJAX aislado**: ninguna vista HTML procesa POST que devuelva JSON; todo va a `src/actions/ajax/`.
- **Administración oculta**: el navegador solo ve `admin_dashboard.php`; los formularios reales viven en `src/admin/`.
- **Animaciones**: carrusel 3D con perspectiva CSS, partículas en canvas, Easter Egg de sakura (5 clics en el logo).

##  Instalación local

```bash
# 1. Clonar el repositorio
git clone https://github.com/tu-usuario/aetheris.git

# 2. Crear el archivo de configuración de BD (NO está en el repo)
cp config/db_config.example.php config/db_config.php
# Editar db_config.php con tus credenciales

# 3. Importar la base de datos
mysql -u root -p documentos < aetheris.sql

# 4. Apuntar el servidor web a la carpeta raíz del proyecto
# Apache: DocumentRoot → /aetheris/
# O usar PHP built-in: php -S localhost:8000
```

##  Sistema de colores

| Sección  | Variable CSS         | Color      |
|----------|----------------------|------------|
| General  | `--primary-blue`     | `#4a8eff`  |
| Anime    | `--anime-color`      | `#9d4edd`  |
| Manga    | `--manga-color`      | `#dd4e4e`  |
| Novela   | `--novela-color`     | `#00c4a0`  |

El color activo se inyecta en cada vista como `--accent-current` en el `<head>`.

---

 | PHP · MySQLi · CSS Variables · Canvas API
