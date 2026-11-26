import re
from datetime import datetime
from decimal import Decimal
from pathlib import Path

from django.apps import apps
from django.conf import settings
from django.contrib.auth import get_user_model
from django.contrib.auth.models import Group
from django.core.exceptions import ImproperlyConfigured
from django.test import TestCase
from django.utils import timezone

from approval_system import settings as ajustes
from publications.roles import GRUPO_ADMINISTRADOR, GRUPO_PUBLICADOR

from .models import (
    CampoBit,
    FavoritoJava,
    ImagenInmuebleJava,
    InmuebleJava,
    ModeloJava,
    Plano2DJava,
    SolicitudJava,
    UsuarioJava,
)

User = get_user_model()
CLAVE = "ClaveSegura#2026"
RAIZ_DJANGO = Path(settings.BASE_DIR)
RAIZ_REPO = RAIZ_DJANGO.parent


def crear_datos_java():
    """Inserta datos como los que dejaría la aplicación Java."""
    ahora = timezone.now()

    laura, carlos = UsuarioJava.objects.bulk_create([
        UsuarioJava(
            nombre="Laura", apellido="Ramírez", email="laura@inmovision.com",
            password="$2a$10$hashbcryptdeprueba", rol="PUBLICADOR",
            fecha_registro=ahora, activo=True,
        ),
        UsuarioJava(
            nombre="Carlos", apellido="Gómez", email="carlos@inmovision.com",
            password="$2a$10$otrohashdeprueba", rol="ADMIN",
            fecha_registro=ahora, activo=False,
        ),
    ])
    laura = UsuarioJava.objects.get(email="laura@inmovision.com")

    InmuebleJava.objects.bulk_create([
        InmuebleJava(
            titulo="Casa en Usaquén", precio=Decimal("350000000.00"),
            direccion="Calle 100 # 15-20", ciudad="Bogotá", tipo="CASA",
            operacion="VENTA", estado="DISPONIBLE",
            estado_publicacion="APROBADA", fecha_publicacion=ahora,
            propietario=laura,
        ),
        InmuebleJava(
            titulo="Apartamento en Chía", precio=Decimal("2400000.00"),
            direccion="Carrera 7 # 12-30", ciudad="Chía", tipo="APARTAMENTO",
            operacion="ARRIENDO", estado="DISPONIBLE",
            estado_publicacion="PENDIENTE", fecha_publicacion=ahora,
            propietario=laura,
        ),
    ])


class ModelosJavaSoloLecturaTests(TestCase):
    """T-01: Django no modifica las tablas de la aplicación Java."""

    def setUp(self):
        crear_datos_java()

    def test_todos_los_modelos_de_java_son_managed_false(self):
        # Durante las pruebas el ejecutor los marca como gestionados para
        # crear tablas temporales; lo que cuenta es lo que dice su Meta.
        modelos = apps.get_app_config("inmovision_java").get_models()

        for modelo in modelos:
            self.assertIs(
                modelo._meta.original_attrs.get("managed"),
                False,
                f"{modelo.__name__} debe ser managed = False",
            )

    def test_los_modelos_apuntan_a_las_tablas_de_hibernate(self):
        tablas = {
            UsuarioJava: "usuarios",
            InmuebleJava: "inmuebles",
            ImagenInmuebleJava: "imagenes_inmueble",
            Plano2DJava: "planos_2d",
            FavoritoJava: "favoritos",
            SolicitudJava: "solicitudes",
        }

        for modelo, tabla in tablas.items():
            self.assertEqual(modelo._meta.db_table, tabla)

    def test_no_hay_migraciones_para_las_tablas_de_java(self):
        carpeta = RAIZ_DJANGO / "inmovision_java" / "migrations"

        self.assertFalse(
            carpeta.exists(),
            "Las tablas de Java no deben tener migraciones de Django.",
        )

    def test_guardar_o_borrar_desde_django_esta_bloqueado(self):
        inmueble = InmuebleJava.objects.first()

        with self.assertRaises(NotImplementedError):
            inmueble.save()

        with self.assertRaises(NotImplementedError):
            inmueble.delete()

        self.assertTrue(issubclass(InmuebleJava, ModeloJava))

    def test_lee_inmuebles_y_sus_relaciones(self):
        casa = InmuebleJava.objects.select_related("propietario").get(
            titulo="Casa en Usaquén"
        )

        self.assertEqual(casa.propietario.nombre, "Laura")
        self.assertEqual(casa.precio, Decimal("350000000.00"))
        self.assertEqual(casa.get_tipo_display(), "Casa")
        self.assertEqual(
            list(UsuarioJava.objects.get(nombre="Laura").inmuebles.values_list(
                "titulo", flat=True
            ).order_by("titulo")),
            ["Apartamento en Chía", "Casa en Usaquén"],
        )

    def test_filtra_por_estado_de_publicacion(self):
        aprobados = InmuebleJava.objects.filter(estado_publicacion="APROBADA")

        self.assertEqual(aprobados.count(), 1)
        self.assertEqual(aprobados.first().titulo, "Casa en Usaquén")


class CampoBitTests(TestCase):
    """
    MariaDB guarda los boolean de Hibernate como bit(1) y el driver los
    entrega como bytes: b'\\x00' y b'\\x01' son ambos "verdaderos" en Python.
    """

    def convertir(self, valor):
        return CampoBit().from_db_value(valor, None, None)

    def test_bytes_de_bit_se_convierten_bien(self):
        self.assertIs(self.convertir(b"\x01"), True)
        self.assertIs(self.convertir(b"\x00"), False)

    def test_enteros_y_nulos(self):
        self.assertIs(self.convertir(1), True)
        self.assertIs(self.convertir(0), False)
        self.assertIsNone(self.convertir(None))


class ZonaHorariaTests(TestCase):

    def test_fechas_de_java_se_leen_en_hora_de_bogota(self):
        # Solo aplica con MySQL/MariaDB, donde se fija TIME_ZONE de la BD.
        if settings.DB_ENGINE != "mysql":
            self.skipTest("Solo con DB_ENGINE=mysql")

        self.assertEqual(
            settings.DATABASES["default"]["TIME_ZONE"], "America/Bogota"
        )


class AdminDatosJavaTests(TestCase):

    def setUp(self):
        crear_datos_java()
        self.admin = User.objects.create_user(
            "admin@demo.com", "admin@demo.com", CLAVE, is_staff=True
        )
        self.admin.groups.add(Group.objects.get(name=GRUPO_ADMINISTRADOR))
        self.client.force_login(self.admin)

    def test_el_panel_lista_los_inmuebles_de_la_base_de_java(self):
        respuesta = self.client.get("/admin/inmovision_java/inmueblejava/")

        self.assertContains(respuesta, "Casa en Usaquén")
        self.assertContains(respuesta, "Apartamento en Chía")

    def test_el_panel_lista_los_usuarios_de_java_sin_mostrar_el_hash(self):
        lista = self.client.get("/admin/inmovision_java/usuariojava/")
        detalle = self.client.get(
            f"/admin/inmovision_java/usuariojava/"
            f"{UsuarioJava.objects.get(nombre='Laura').pk}/change/"
        )

        self.assertContains(lista, "laura@inmovision.com")
        self.assertNotContains(lista, "$2a$")
        self.assertNotContains(detalle, "$2a$")

    def test_los_activos_se_muestran_correctamente(self):
        self.assertEqual(UsuarioJava.objects.filter(activo=True).count(), 1)
        self.assertEqual(UsuarioJava.objects.filter(activo=False).count(), 1)

    def test_no_se_puede_agregar_editar_ni_borrar(self):
        inmueble = InmuebleJava.objects.first()
        base = "/admin/inmovision_java/inmueblejava/"

        self.assertEqual(self.client.get(f"{base}add/").status_code, 403)
        self.assertEqual(
            self.client.post(f"{base}{inmueble.pk}/delete/", {"post": "yes"}).status_code,
            403,
        )
        self.assertEqual(
            self.client.post(
                f"{base}{inmueble.pk}/change/", {"titulo": "Cambiado"}
            ).status_code,
            403,
        )
        inmueble.refresh_from_db()
        self.assertNotEqual(inmueble.titulo, "Cambiado")

    def test_un_publicador_no_ve_los_datos_de_java(self):
        publicador = User.objects.create_user(
            "pablo@demo.com", "pablo@demo.com", CLAVE, is_staff=True
        )
        publicador.groups.add(Group.objects.get(name=GRUPO_PUBLICADOR))
        self.client.force_login(publicador)

        respuesta = self.client.get("/admin/inmovision_java/inmueblejava/")

        self.assertEqual(respuesta.status_code, 403)


class ConfiguracionSinSecretosTests(TestCase):
    """T-01: ninguna credencial ni clave vive en el código del repositorio."""

    def leer(self, ruta):
        return Path(ruta).read_text(encoding="utf-8")

    def test_settings_no_contiene_claves_ni_contrasenas(self):
        codigo = self.leer(RAIZ_DJANGO / "approval_system" / "settings.py")

        self.assertNotIn("django-insecure", codigo)
        self.assertIsNone(
            re.search(r"""['"]PASSWORD['"]\s*:\s*['"][^'"]+['"]""", codigo),
            "La contraseña de la BD no puede estar escrita en settings.py",
        )
        self.assertIsNone(
            re.search(r"""SECRET_KEY\s*=\s*['"][^'"]+['"]""", codigo),
            "SECRET_KEY no puede estar escrita en settings.py",
        )

    def test_existe_env_example_sin_valores_reales(self):
        ruta = RAIZ_DJANGO / ".env.example"
        self.assertTrue(ruta.exists(), "Falta .env.example")

        valores = {}
        for linea in self.leer(ruta).splitlines():
            linea = linea.strip()
            if linea and not linea.startswith("#") and "=" in linea:
                nombre, valor = linea.split("=", 1)
                valores[nombre.strip()] = valor.strip()

        for secreto in ("DJANGO_SECRET_KEY", "DB_USER", "DB_PASSWORD"):
            self.assertIn(secreto, valores)
            self.assertEqual(
                valores[secreto], "", f"{secreto} debe ir vacío en .env.example"
            )

    def test_gitignore_ignora_env_pero_no_la_plantilla(self):
        ruta = RAIZ_REPO / ".gitignore"
        if not ruta.exists():
            self.skipTest("No se ejecuta dentro del repositorio completo")

        lineas = [l.strip() for l in self.leer(ruta).splitlines()]

        self.assertIn(".env", lineas)
        self.assertIn("!.env.example", lineas)

    def test_existe_requirements_con_las_dependencias_clave(self):
        ruta = RAIZ_DJANGO / "requirements.txt"
        self.assertTrue(ruta.exists(), "Falta requirements.txt")

        contenido = self.leer(ruta).lower()
        for paquete in ("django", "mysqlclient", "python-dotenv"):
            self.assertIn(paquete, contenido)

    def test_variable_obligatoria_faltante_da_un_error_claro(self):
        with self.assertRaises(ImproperlyConfigured) as contexto:
            ajustes._requerida("VARIABLE_QUE_NO_EXISTE_XYZ")

        self.assertIn(".env.example", str(contexto.exception))

    def test_lectura_de_booleanos_del_entorno(self):
        import os

        for texto, esperado in (("True", True), ("1", True), ("si", True),
                                ("false", False), ("0", False), ("no", False)):
            os.environ["PRUEBA_BOOL"] = texto
            self.assertIs(ajustes._booleana("PRUEBA_BOOL"), esperado, texto)

        del os.environ["PRUEBA_BOOL"]
        self.assertIs(ajustes._booleana("PRUEBA_BOOL", True), True)
