"""
Módulo complementario de InmoVision 3D (Django).

Comparte la base de datos MySQL/MariaDB de la aplicación Java:
  - Las tablas de Java se mapean con modelos `managed = False` (Django NO las modifica).
  - Las tablas que necesita Django (admin, sesiones, ubicaciones del mapa, ...) las crea `migrate`.

Ninguna credencial vive en el código: todo sale del archivo .env (ver .env.example).
"""
import os
from pathlib import Path

from django.core.exceptions import ImproperlyConfigured
from dotenv import load_dotenv

BASE_DIR = Path(__file__).resolve().parent.parent

load_dotenv(BASE_DIR / ".env")


def requerida(nombre, permitir_vacia=False):
    """Lee una variable obligatoria del entorno; si falta, explica cómo arreglarlo."""
    valor = os.environ.get(nombre)
    if valor is None or (valor == "" and not permitir_vacia):
        raise ImproperlyConfigured(
            f"Falta la variable de entorno {nombre}. "
            "Copia .env.example como .env y completa tus valores (ver README)."
        )
    return valor


SECRET_KEY = requerida("DJANGO_SECRET_KEY")
DEBUG = os.environ.get("DJANGO_DEBUG", "False").lower() in ("1", "true", "yes")
ALLOWED_HOSTS = [h.strip() for h in os.environ.get("DJANGO_ALLOWED_HOSTS", "localhost,127.0.0.1").split(",") if h.strip()]

INSTALLED_APPS = [
    "django.contrib.admin",
    "django.contrib.auth",
    "django.contrib.contenttypes",
    "django.contrib.sessions",
    "django.contrib.messages",
    "django.contrib.staticfiles",
    "core",
]

MIDDLEWARE = [
    "django.middleware.security.SecurityMiddleware",
    "django.contrib.sessions.middleware.SessionMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",          # protección CSRF
    "django.contrib.auth.middleware.AuthenticationMiddleware",
    "django.contrib.messages.middleware.MessageMiddleware",
    "django.middleware.clickjacking.XFrameOptionsMiddleware",
]

ROOT_URLCONF = "config.urls"

TEMPLATES = [
    {
        "BACKEND": "django.template.backends.django.DjangoTemplates",
        "DIRS": [],
        "APP_DIRS": True,
        "OPTIONS": {
            "context_processors": [
                "django.template.context_processors.debug",
                "django.template.context_processors.request",
                "django.contrib.auth.context_processors.auth",
                "django.contrib.messages.context_processors.messages",
            ],
        },
    }
]

WSGI_APPLICATION = "config.wsgi.application"

# Misma base de datos que la aplicación Java
DATABASES = {
    "default": {
        "ENGINE": "django.db.backends.mysql",   # conector: mysqlclient
        "NAME": requerida("DB_NAME"),
        "USER": requerida("DB_USER"),
        "PASSWORD": requerida("DB_PASSWORD", permitir_vacia=True),  # XAMPP: root sin clave
        "HOST": requerida("DB_HOST"),
        "PORT": requerida("DB_PORT"),
        "OPTIONS": {"charset": "utf8mb4"},
    }
}

# Dónde corre la aplicación Java (fotos /uploads/... y enlaces a detalle y recorrido 3D)
SPRING_BASE_URL = os.environ.get("SPRING_BASE_URL", "http://localhost:8080").rstrip("/")

LANGUAGE_CODE = "es"
TIME_ZONE = "America/Bogota"
USE_I18N = True
USE_TZ = False  # Java guarda LocalDateTime sin zona horaria

STATIC_URL = "static/"
DEFAULT_AUTO_FIELD = "django.db.models.BigAutoField"
