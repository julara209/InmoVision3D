
from decimal import Decimal

from pathlib import Path

from django.conf import settings
from django.contrib.auth import get_user_model
from django.contrib.auth.models import Group
from django.contrib.staticfiles import finders
from django.core.exceptions import ValidationError
from django.core.files.uploadedfile import SimpleUploadedFile
from django.test import Client, TestCase
from django.urls import reverse

from .forms import MENSAJE_LOGIN_INVALIDO
from .models import Inmueble, obtener_inmuebles_publicados
from .roles import (
    GRUPO_ADMINISTRADOR,
    GRUPO_CLIENTE,
    GRUPO_PUBLICADOR,
)

User = get_user_model()
CLAVE = "ClaveSegura#2026"


class InmuebleTests(TestCase):

    def setUp(self):
        self.inmueble = Inmueble.objects.create(
            java_id=999991,
            titulo="Casa de prueba",
            descripcion="Inmueble creado para pruebas",
            precio=Decimal("150000000"),
            direccion="Calle de prueba 123",
            ciudad="Bogota",
            tipo=Inmueble.TipoInmueble.CASA,
            operacion=Inmueble.TipoOperacion.VENTA,
            estado_comercial=Inmueble.EstadoComercial.DISPONIBLE,
            estado_revision=Inmueble.EstadoRevision.PENDIENTE,
            propietario_java_id=1,
            propietario_nombre="Usuario",
            propietario_apellido="Prueba",
            propietario_email="prueba@example.com",
            propietario_telefono="3001234567",
        )

    def test_inmueble_se_crea_correctamente(self):
        self.assertEqual(self.inmueble.titulo, "Casa de prueba")
        self.assertEqual(
            self.inmueble.estado_revision,
            Inmueble.EstadoRevision.PENDIENTE
        )

    def test_un_inmueble_pendiente_no_se_publica(self):
        publicaciones = obtener_inmuebles_publicados()

        self.assertEqual(publicaciones.count(), 0)

    def test_solo_los_inmuebles_aprobados_se_publican(self):
        self.inmueble.estado_revision = Inmueble.EstadoRevision.APROBADA
        self.inmueble.save()

        publicaciones = obtener_inmuebles_publicados()

        self.assertEqual(publicaciones.count(), 1)
        self.assertEqual(publicaciones.first(), self.inmueble)

    def test_rechazo_debe_tener_observacion(self):
        self.inmueble.estado_revision = Inmueble.EstadoRevision.RECHAZADA
        self.inmueble.observacion_revision = ""

        with self.assertRaises(ValidationError):
            self.inmueble.full_clean()

    def test_devolucion_debe_tener_observacion(self):
        self.inmueble.estado_revision = Inmueble.EstadoRevision.DEVUELTA
        self.inmueble.observacion_revision = ""

        with self.assertRaises(ValidationError):
            self.inmueble.full_clean()

    def test_rechazo_con_observacion_es_valido(self):
        self.inmueble.estado_revision = Inmueble.EstadoRevision.RECHAZADA
        self.inmueble.observacion_revision = "Debe corregir la descripción."

        self.inmueble.full_clean()

    def test_devolucion_con_observacion_es_valida(self):
        self.inmueble.estado_revision = Inmueble.EstadoRevision.DEVUELTA
        self.inmueble.observacion_revision = "Debe completar la información."

        self.inmueble.full_clean()

    def test_precio_debe_ser_mayor_que_cero(self):
        self.inmueble.precio = Decimal("0")

        with self.assertRaises(ValidationError):
            self.inmueble.full_clean()



# ---------------------------------------------------------------------------
# HU-001, HU-002 y HU-003: acceso, registro y roles
# ---------------------------------------------------------------------------

def crear_usuario(correo, grupo=None, staff=False, superusuario=False):
    if superusuario:
        usuario = User.objects.create_superuser(correo, correo, CLAVE)
    else:
        usuario = User.objects.create_user(
            username=correo,
            email=correo,
            password=CLAVE,
            first_name=correo.split("@")[0],
            is_staff=staff,
        )

    if grupo:
        usuario.groups.add(Group.objects.get(name=grupo))

    return usuario


def crear_inmueble(java_id, titulo, publicador=None,
                   estado=Inmueble.EstadoRevision.PENDIENTE):
    return Inmueble.objects.create(
        java_id=java_id,
        titulo=titulo,
        precio=Decimal("100000000"),
        direccion="Calle 1 # 2-3",
        ciudad="Bogotá",
        tipo=Inmueble.TipoInmueble.CASA,
        operacion=Inmueble.TipoOperacion.VENTA,
        estado_revision=estado,
        publicador=publicador,
    )


class RolesTests(TestCase):

    def test_los_tres_grupos_se_crean_al_migrar(self):
        nombres = set(Group.objects.values_list("name", flat=True))

        self.assertTrue(
            {GRUPO_ADMINISTRADOR, GRUPO_PUBLICADOR, GRUPO_CLIENTE} <= nombres
        )

    def test_el_cliente_solo_tiene_permiso_de_consulta(self):
        permisos = set(
            Group.objects.get(name=GRUPO_CLIENTE)
            .permissions.values_list("codename", flat=True)
        )

        self.assertEqual(permisos, {"view_inmueble"})

    def test_el_publicador_puede_crear_pero_no_borrar_inmuebles(self):
        permisos = set(
            Group.objects.get(name=GRUPO_PUBLICADOR)
            .permissions.values_list("codename", flat=True)
        )

        self.assertIn("add_inmueble", permisos)
        self.assertNotIn("delete_inmueble", permisos)

    def test_el_administrador_tiene_permisos_completos(self):
        permisos = set(
            Group.objects.get(name=GRUPO_ADMINISTRADOR)
            .permissions.values_list("codename", flat=True)
        )

        for codename in (
            "add_inmueble", "change_inmueble",
            "view_inmueble", "delete_inmueble",
        ):
            self.assertIn(codename, permisos)


class InicioSesionTests(TestCase):

    def setUp(self):
        self.publicador = crear_usuario("pablo@demo.com", GRUPO_PUBLICADOR)
        self.cliente = crear_usuario("clara@demo.com", GRUPO_CLIENTE)

    def test_publicador_entra_a_su_panel(self):
        respuesta = self.client.post(
            reverse("login"),
            {"username": "pablo@demo.com", "password": CLAVE},
            follow=True,
        )

        self.assertRedirects(respuesta, reverse("panel_publicador"))

    def test_cliente_entra_al_catalogo(self):
        respuesta = self.client.post(
            reverse("login"),
            {"username": "clara@demo.com", "password": CLAVE},
            follow=True,
        )

        self.assertRedirects(respuesta, reverse("catalogo"))

    def test_el_correo_no_distingue_mayusculas(self):
        respuesta = self.client.post(
            reverse("login"),
            {"username": "PABLO@Demo.com", "password": CLAVE},
        )

        self.assertEqual(respuesta.status_code, 302)

    def test_contrasena_incorrecta_muestra_mensaje_claro(self):
        respuesta = self.client.post(
            reverse("login"),
            {"username": "pablo@demo.com", "password": "otra-clave"},
        )

        self.assertEqual(respuesta.status_code, 200)
        self.assertContains(respuesta, MENSAJE_LOGIN_INVALIDO)

    def test_no_se_revela_si_fallo_el_correo_o_la_contrasena(self):
        mal_clave = self.client.post(
            reverse("login"),
            {"username": "pablo@demo.com", "password": "otra-clave"},
        )
        mal_correo = self.client.post(
            reverse("login"),
            {"username": "nadie@demo.com", "password": CLAVE},
        )

        self.assertContains(mal_clave, MENSAJE_LOGIN_INVALIDO)
        self.assertContains(mal_correo, MENSAJE_LOGIN_INVALIDO)
        self.assertNotContains(mal_correo, "no existe")

    def test_cuenta_desactivada_muestra_el_mismo_mensaje(self):
        self.publicador.is_active = False
        self.publicador.save()

        respuesta = self.client.post(
            reverse("login"),
            {"username": "pablo@demo.com", "password": CLAVE},
        )

        self.assertContains(respuesta, MENSAJE_LOGIN_INVALIDO)

    def test_la_contrasena_se_guarda_cifrada(self):
        self.assertNotEqual(self.publicador.password, CLAVE)
        self.assertTrue(self.publicador.password.startswith("pbkdf2_"))

    def test_la_contrasena_nunca_aparece_en_la_respuesta(self):
        respuesta = self.client.post(
            reverse("login"),
            {"username": "pablo@demo.com", "password": "otra-clave"},
        )

        self.assertNotContains(respuesta, "otra-clave")

    def test_usuario_sin_rol_ve_acceso_denegado(self):
        User.objects.create_user("sinrol@demo.com", "sinrol@demo.com", CLAVE)
        self.client.login(username="sinrol@demo.com", password=CLAVE)

        respuesta = self.client.get(reverse("inicio"))

        self.assertEqual(respuesta.status_code, 403)


class AislamientoDelPublicadorTests(TestCase):

    def setUp(self):
        self.ana = crear_usuario("ana@demo.com", GRUPO_PUBLICADOR)
        self.beto = crear_usuario("beto@demo.com", GRUPO_PUBLICADOR)
        self.de_ana = crear_inmueble(1, "Casa de Ana", self.ana)
        self.de_beto = crear_inmueble(2, "Casa de Beto", self.beto)

    def test_el_panel_solo_muestra_las_propiedades_propias(self):
        self.client.force_login(self.ana)

        respuesta = self.client.get(reverse("panel_publicador"))

        self.assertContains(respuesta, "Casa de Ana")
        self.assertNotContains(respuesta, "Casa de Beto")

    def test_puede_abrir_su_propia_propiedad(self):
        self.client.force_login(self.ana)

        respuesta = self.client.get(
            reverse("detalle_inmueble_propio", args=[self.de_ana.pk])
        )

        self.assertEqual(respuesta.status_code, 200)

    def test_no_puede_abrir_la_propiedad_de_otro_por_la_direccion(self):
        self.client.force_login(self.ana)

        respuesta = self.client.get(
            reverse("detalle_inmueble_propio", args=[self.de_beto.pk])
        )

        self.assertEqual(respuesta.status_code, 404)

    def test_mis_observaciones_solo_muestra_las_propias(self):
        for inmueble in (self.de_ana, self.de_beto):
            inmueble.estado_revision = Inmueble.EstadoRevision.RECHAZADA
            inmueble.observacion_revision = "Falta información."
            inmueble.save()

        self.client.force_login(self.ana)

        respuesta = self.client.get(reverse("mis_observaciones"))

        self.assertContains(respuesta, "Casa de Ana")
        self.assertNotContains(respuesta, "Casa de Beto")

    def test_sin_sesion_redirige_al_login(self):
        url = reverse("panel_publicador")

        respuesta = self.client.get(url)

        self.assertRedirects(
            respuesta,
            f"{reverse('login')}?next={url}",
            fetch_redirect_response=False,
        )


class CerrarSesionTests(TestCase):

    def setUp(self):
        self.usuario = crear_usuario("ana@demo.com", GRUPO_PUBLICADOR)
        self.client.force_login(self.usuario)

    def test_cerrar_sesion_vuelve_al_login(self):
        respuesta = self.client.post(reverse("logout"))

        self.assertRedirects(respuesta, reverse("login"))

    def test_despues_de_cerrar_sesion_no_se_accede_a_paginas_privadas(self):
        self.client.post(reverse("logout"))

        respuesta = self.client.get(reverse("panel_publicador"))

        self.assertEqual(respuesta.status_code, 302)
        self.assertIn(reverse("login"), respuesta["Location"])

    def test_las_paginas_privadas_no_se_guardan_en_cache(self):
        # Esto evita que el botón "atrás" muestre contenido tras cerrar sesión.
        for nombre in ("panel_publicador", "catalogo",
                       "mis_observaciones", "cargar_inmuebles_csv"):
            respuesta = self.client.get(reverse(nombre))

            self.assertIn(
                "no-store",
                respuesta["Cache-Control"],
                f"{nombre} se puede guardar en caché",
            )

    def test_cerrar_sesion_por_get_no_esta_permitido(self):
        respuesta = self.client.get(reverse("logout"))

        self.assertEqual(respuesta.status_code, 405)


class RegistroClienteTests(TestCase):

    def datos(self, **cambios):
        datos = {
            "nombre": "Carlos",
            "correo": "carlos@demo.com",
            "password1": CLAVE,
            "password2": CLAVE,
        }
        datos.update(cambios)
        return datos

    def test_persona_nueva_queda_con_rol_cliente(self):
        respuesta = self.client.post(reverse("registro"), self.datos())

        self.assertRedirects(respuesta, reverse("login"))

        usuario = User.objects.get(username="carlos@demo.com")
        self.assertEqual(usuario.first_name, "Carlos")
        self.assertTrue(usuario.groups.filter(name=GRUPO_CLIENTE).exists())
        self.assertFalse(usuario.groups.filter(name=GRUPO_PUBLICADOR).exists())
        self.assertFalse(usuario.is_staff)
        self.assertFalse(usuario.is_superuser)

    def test_la_contrasena_se_guarda_cifrada(self):
        self.client.post(reverse("registro"), self.datos())

        usuario = User.objects.get(username="carlos@demo.com")

        self.assertNotEqual(usuario.password, CLAVE)
        self.assertTrue(usuario.check_password(CLAVE))

    def test_no_se_registra_dos_veces_el_mismo_correo(self):
        self.client.post(reverse("registro"), self.datos())
        respuesta = self.client.post(reverse("registro"), self.datos())

        self.assertContains(respuesta, "Ya existe una cuenta")
        self.assertEqual(User.objects.filter(email="carlos@demo.com").count(), 1)

    def test_el_correo_repetido_se_detecta_sin_importar_mayusculas(self):
        self.client.post(reverse("registro"), self.datos())
        respuesta = self.client.post(
            reverse("registro"), self.datos(correo="CARLOS@Demo.com")
        )

        self.assertContains(respuesta, "Ya existe una cuenta")
        self.assertEqual(User.objects.count(), 1)

    def test_correo_con_formato_invalido(self):
        respuesta = self.client.post(
            reverse("registro"), self.datos(correo="no-es-un-correo")
        )

        self.assertContains(respuesta, "correo electrónico válido")
        self.assertEqual(User.objects.count(), 0)

    def test_contrasena_demasiado_corta(self):
        respuesta = self.client.post(
            reverse("registro"), self.datos(password1="Ab1#", password2="Ab1#")
        )

        self.assertContains(respuesta, "demasiado corta")
        self.assertEqual(User.objects.count(), 0)

    def test_las_contrasenas_deben_coincidir(self):
        respuesta = self.client.post(
            reverse("registro"), self.datos(password2="OtraClave#2026")
        )

        self.assertContains(respuesta, "no coinciden")
        self.assertEqual(User.objects.count(), 0)

    def test_nombre_obligatorio(self):
        respuesta = self.client.post(reverse("registro"), self.datos(nombre="   "))

        self.assertContains(respuesta, "El nombre es obligatorio.")
        self.assertEqual(User.objects.count(), 0)

    def test_despues_de_registrarse_puede_iniciar_sesion(self):
        self.client.post(reverse("registro"), self.datos())

        respuesta = self.client.post(
            reverse("login"),
            {"username": "carlos@demo.com", "password": CLAVE},
            follow=True,
        )

        self.assertRedirects(respuesta, reverse("catalogo"))

    def test_la_contrasena_no_se_devuelve_en_la_pagina(self):
        respuesta = self.client.post(
            reverse("registro"), self.datos(password2="OtraClave#2026")
        )

        self.assertNotContains(respuesta, CLAVE)


class PermisosPorRolTests(TestCase):

    def setUp(self):
        self.cliente = crear_usuario("clara@demo.com", GRUPO_CLIENTE)
        self.publicador = crear_usuario("pablo@demo.com", GRUPO_PUBLICADOR)
        self.admin = crear_usuario(
            "admin@demo.com", GRUPO_ADMINISTRADOR, staff=True
        )

    def test_cliente_no_accede_a_vistas_de_publicador(self):
        self.client.force_login(self.cliente)

        for nombre in ("panel_publicador", "cargar_inmuebles_csv",
                       "mis_observaciones"):
            respuesta = self.client.get(reverse(nombre))

            self.assertEqual(respuesta.status_code, 403, nombre)
            self.assertContains(respuesta, "Acceso denegado", status_code=403)

    def test_cliente_no_puede_cargar_inmuebles_por_post(self):
        self.client.force_login(self.cliente)
        archivo = SimpleUploadedFile("x.csv", b"a,b\n1,2\n")

        respuesta = self.client.post(
            reverse("cargar_inmuebles_csv"), {"archivo": archivo}
        )

        self.assertEqual(respuesta.status_code, 403)
        self.assertEqual(Inmueble.objects.count(), 0)

    def test_cliente_no_accede_al_panel_de_administracion(self):
        self.client.force_login(self.cliente)

        respuesta = self.client.get("/admin/")

        self.assertEqual(respuesta.status_code, 302)
        self.assertIn("/admin/login/", respuesta["Location"])

    def test_publicador_no_accede_al_panel_de_administracion(self):
        self.client.force_login(self.publicador)

        respuesta = self.client.get("/admin/publications/inmueble/")

        self.assertEqual(respuesta.status_code, 302)
        self.assertIn("/admin/login/", respuesta["Location"])

    def test_publicador_con_acceso_staff_por_error_sigue_sin_ver_inmuebles(self):
        self.publicador.is_staff = True
        self.publicador.save()
        self.client.force_login(self.publicador)

        respuesta = self.client.get("/admin/publications/inmueble/")

        self.assertEqual(respuesta.status_code, 403)

    def test_publicador_no_puede_aprobar_ni_siquiera_por_post(self):
        self.publicador.is_staff = True
        self.publicador.save()
        propio = crear_inmueble(10, "Propio", self.publicador)
        self.client.force_login(self.publicador)

        self.client.post(
            "/admin/publications/inmueble/",
            {
                "action": "aprobar_publicaciones",
                "_selected_action": [propio.pk],
            },
        )

        propio.refresh_from_db()
        self.assertEqual(propio.estado_revision, Inmueble.EstadoRevision.PENDIENTE)

    def test_administrador_aprueba_publicaciones_de_otros(self):
        ajeno = crear_inmueble(11, "Ajeno", self.publicador)
        self.client.force_login(self.admin)

        self.client.post(
            "/admin/publications/inmueble/",
            {
                "action": "aprobar_publicaciones",
                "_selected_action": [ajeno.pk],
            },
        )

        ajeno.refresh_from_db()
        self.assertEqual(ajeno.estado_revision, Inmueble.EstadoRevision.APROBADA)
        self.assertEqual(ajeno.administrador_revisor, self.admin)

    def test_administrador_no_aprueba_sus_propias_publicaciones(self):
        propio = crear_inmueble(12, "Del admin", self.admin)
        self.client.force_login(self.admin)

        self.client.post(
            "/admin/publications/inmueble/",
            {
                "action": "aprobar_publicaciones",
                "_selected_action": [propio.pk],
            },
        )

        propio.refresh_from_db()
        self.assertEqual(propio.estado_revision, Inmueble.EstadoRevision.PENDIENTE)

    def test_cliente_solo_ve_inmuebles_aprobados_en_el_catalogo(self):
        crear_inmueble(20, "Aprobado", self.publicador,
                       Inmueble.EstadoRevision.APROBADA)
        crear_inmueble(21, "Pendiente", self.publicador,
                       Inmueble.EstadoRevision.PENDIENTE)
        crear_inmueble(22, "Rechazado", self.publicador,
                       Inmueble.EstadoRevision.RECHAZADA)
        self.client.force_login(self.cliente)

        respuesta = self.client.get(reverse("catalogo"))

        self.assertContains(respuesta, "Aprobado")
        self.assertNotContains(respuesta, "Pendiente")
        self.assertNotContains(respuesta, "Rechazado")

    def test_cliente_no_abre_un_inmueble_no_aprobado_por_la_direccion(self):
        pendiente = crear_inmueble(23, "Pendiente", self.publicador)
        self.client.force_login(self.cliente)

        respuesta = self.client.get(
            reverse("detalle_inmueble_publico", args=[pendiente.pk])
        )

        self.assertEqual(respuesta.status_code, 404)

    def test_publicador_carga_csv_y_queda_como_dueno(self):
        contenido = (
            "java_id,titulo,descripcion,precio,direccion,ciudad,tipo,"
            "operacion,estado_comercial,area,habitaciones,banos\n"
            "700001,Casa CSV,Desc,250000000,Calle 1,Bogota,CASA,VENTA,"
            "DISPONIBLE,100,3,2\n"
        )
        archivo = SimpleUploadedFile("inmuebles.csv", contenido.encode("utf-8"))
        self.client.force_login(self.publicador)

        self.client.post(reverse("cargar_inmuebles_csv"), {"archivo": archivo})

        inmueble = Inmueble.objects.get(java_id=700001)
        self.assertEqual(inmueble.publicador, self.publicador)
        self.assertEqual(inmueble.estado_revision, Inmueble.EstadoRevision.PENDIENTE)


class ProteccionCsrfTests(TestCase):

    def test_login_sin_token_csrf_es_rechazado(self):
        crear_usuario("pablo@demo.com", GRUPO_PUBLICADOR)
        cliente = Client(enforce_csrf_checks=True)

        respuesta = cliente.post(
            reverse("login"),
            {"username": "pablo@demo.com", "password": CLAVE},
        )

        self.assertEqual(respuesta.status_code, 403)

    def test_registro_sin_token_csrf_es_rechazado(self):
        cliente = Client(enforce_csrf_checks=True)

        respuesta = cliente.post(
            reverse("registro"),
            {
                "nombre": "X", "correo": "x@demo.com",
                "password1": CLAVE, "password2": CLAVE,
            },
        )

        self.assertEqual(respuesta.status_code, 403)
        self.assertEqual(User.objects.count(), 0)

    def test_cerrar_sesion_sin_token_csrf_es_rechazado(self):
        usuario = crear_usuario("pablo@demo.com", GRUPO_PUBLICADOR)
        cliente = Client(enforce_csrf_checks=True)
        cliente.force_login(usuario)

        respuesta = cliente.post(reverse("logout"))

        self.assertEqual(respuesta.status_code, 403)

    def test_los_formularios_incluyen_el_token_csrf(self):
        for nombre in ("login", "registro"):
            respuesta = self.client.get(reverse(nombre))

            self.assertContains(respuesta, "csrfmiddlewaretoken")



# ---------------------------------------------------------------------------
# Diseño: las pantallas usan el mismo CSS, logo y tema que la aplicación Java
# ---------------------------------------------------------------------------

ESTATICOS = (
    "publications/css/styles.css",
    "publications/css/django.css",
    "publications/img/logo.png",
    "publications/js/theme.js",
    "publications/js/perfil.js",
)


class DisenoIgualAJavaTests(TestCase):

    def test_existen_todos_los_archivos_estaticos(self):
        for ruta in ESTATICOS:
            self.assertIsNotNone(
                finders.find(ruta), f"Falta el archivo estático {ruta}"
            )

    def test_el_css_es_copia_exacta_del_de_java(self):
        # Solo se puede comparar cuando se ejecuta dentro del repositorio
        # completo. Si falla: vuelve a copiar styles.css desde Java.
        css_java = (
            Path(settings.BASE_DIR).parent
            / "src" / "main" / "resources" / "static" / "css" / "styles.css"
        )
        if not css_java.exists():
            self.skipTest("No se ejecuta dentro del repositorio completo")

        css_django = Path(finders.find("publications/css/styles.css"))

        def normalizar(ruta):
            return ruta.read_bytes().replace(b"\r\n", b"\n")

        self.assertEqual(
            normalizar(css_django),
            normalizar(css_java),
            "styles.css cambió en Java: vuelve a copiarlo a "
            "django_approval/publications/static/publications/css/",
        )

    def test_login_y_registro_cargan_el_css_y_el_tema_de_java(self):
        for nombre in ("login", "registro"):
            respuesta = self.client.get(reverse(nombre))

            for ruta in ("css/styles.css", "css/django.css", "js/theme.js"):
                self.assertContains(respuesta, f"publications/{ruta}")

            for clase in ("adm-header", "auth-box", "form-control", "btn-primary"):
                self.assertContains(respuesta, clase)

    def test_el_login_ofrece_ver_la_contrasena(self):
        respuesta = self.client.get(reverse("login"))

        self.assertContains(respuesta, "pw-toggle")

    def test_la_barra_de_un_publicador_muestra_su_rol_y_cierra_sesion_por_post(self):
        usuario = crear_usuario("pablo@demo.com", GRUPO_PUBLICADOR)
        self.client.force_login(usuario)

        respuesta = self.client.get(reverse("panel_publicador"))

        self.assertContains(respuesta, "adm-profile")
        self.assertContains(respuesta, "rol-badge")
        self.assertContains(respuesta, 'method="post" action="/publicaciones/logout/"')

    def test_el_anonimo_ve_enlaces_de_acceso_en_la_barra(self):
        respuesta = self.client.get(reverse("login"))

        self.assertContains(respuesta, reverse("registro"))
        self.assertNotContains(respuesta, "adm-profile")

    def test_los_precios_se_muestran_con_separador_de_miles(self):
        usuario = crear_usuario("pablo@demo.com", GRUPO_PUBLICADOR)
        crear_inmueble(900, "Casa cara", usuario)
        self.client.force_login(usuario)

        respuesta = self.client.get(reverse("panel_publicador"))

        self.assertNotContains(respuesta, "$100000000")
        self.assertRegex(respuesta.content.decode(), r"\$100\s?000\s?000|\$100\.000\.000")
