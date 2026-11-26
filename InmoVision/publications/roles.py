"""
Roles del sistema y permisos de cada uno.

Los tres roles son grupos de Django:

* Administrador: aprueba, rechaza y devuelve publicaciones.
* Publicador:    registra sus inmuebles y los carga de forma masiva.
* Cliente:       solo consulta inmuebles aprobados.

Un superusuario de Django cuenta siempre como Administrador.
"""

from django.apps import apps as registro_apps
from django.contrib.auth.management import create_permissions
from django.contrib.auth.models import Group, Permission

GRUPO_ADMINISTRADOR = "Administrador"
GRUPO_PUBLICADOR = "Publicador"
GRUPO_CLIENTE = "Cliente"

ROL_ADMINISTRADOR = "administrador"
ROL_PUBLICADOR = "publicador"
ROL_CLIENTE = "cliente"

# Permisos de cada grupo (codename de los permisos que Django crea
# automáticamente para cada modelo: add_, change_, view_, delete_).
PERMISOS_PUBLICADOR = [
    "add_inmueble",
    "change_inmueble",
    "view_inmueble",
]

PERMISOS_CLIENTE = [
    "view_inmueble",
]


def _pertenece(usuario, nombre_grupo):
    return usuario.groups.filter(name=nombre_grupo).exists()


def es_administrador(usuario):
    return usuario.is_authenticated and (
        usuario.is_superuser or _pertenece(usuario, GRUPO_ADMINISTRADOR)
    )


def es_publicador(usuario):
    return usuario.is_authenticated and _pertenece(usuario, GRUPO_PUBLICADOR)


def es_cliente(usuario):
    return usuario.is_authenticated and _pertenece(usuario, GRUPO_CLIENTE)


def rol_de(usuario):
    """
    Devuelve el rol principal del usuario. Si tiene varios, manda el de
    mayor privilegio (administrador > publicador > cliente).
    """
    if es_administrador(usuario):
        return ROL_ADMINISTRADOR
    if es_publicador(usuario):
        return ROL_PUBLICADOR
    if es_cliente(usuario):
        return ROL_CLIENTE
    return None


APPS_CON_PERMISOS = ("publications", "inmovision_java")


def _asegurar_permisos_de_modelos():
    """
    Django crea los permisos de cada app al final de SU migración, y el orden
    entre apps no está garantizado. Se crean aquí de forma explícita para
    poder asignarlos sin importar quién terminó primero.
    """
    for etiqueta in APPS_CON_PERMISOS:
        create_permissions(registro_apps.get_app_config(etiqueta), verbosity=0)


def crear_grupos_y_permisos():
    """
    Crea los tres grupos y les asigna sus permisos. Se puede ejecutar
    cuantas veces se quiera sin duplicar nada.
    """
    _asegurar_permisos_de_modelos()

    administrador, _ = Group.objects.get_or_create(name=GRUPO_ADMINISTRADOR)
    publicador, _ = Group.objects.get_or_create(name=GRUPO_PUBLICADOR)
    cliente, _ = Group.objects.get_or_create(name=GRUPO_CLIENTE)

    # Administrador: permisos completos sobre los modelos de la app y solo
    # lectura sobre los datos de la aplicación Java (inmovision_java).
    administrador.permissions.set(
        Permission.objects.filter(content_type__app_label="publications")
        | Permission.objects.filter(
            content_type__app_label="inmovision_java",
            codename__startswith="view_",
        )
    )

    publicador.permissions.set(
        Permission.objects.filter(
            content_type__app_label="publications",
            codename__in=PERMISOS_PUBLICADOR,
        )
    )

    cliente.permissions.set(
        Permission.objects.filter(
            content_type__app_label="publications",
            codename__in=PERMISOS_CLIENTE,
        )
    )

    return administrador, publicador, cliente
