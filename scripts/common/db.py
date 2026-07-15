# ══════════════════════════════════════════════════════════════════════════
# AETHERIS — DB.PY
# Conexión a MySQL compartida por los scripts, usando credenciales de .env.
# ══════════════════════════════════════════════════════════════════════════
import os
import sys

try:
    import pymysql
except ImportError:
    sys.exit("Falta pymysql. Instalá las dependencias con: pip install -r requirements.txt")

from dotenv import load_dotenv

load_dotenv()


def get_connection():
    try:
        return pymysql.connect(
            host=os.getenv("DB_HOST", "localhost"),
            user=os.getenv("DB_USER", "root"),
            password=os.getenv("DB_PASS", ""),
            database=os.getenv("DB_NAME", "aetheris"),
            charset="utf8mb4",
            cursorclass=pymysql.cursors.DictCursor,
        )
    except pymysql.MySQLError as e:
        sys.exit(f"No se pudo conectar a la base de datos: {e}")
