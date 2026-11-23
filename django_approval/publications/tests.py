
from decimal import Decimal

from django.contrib.auth.models import User
from django.test import TestCase

from .models import Inmueble


class InmuebleTests(TestCase):

    def setUp(self):
        self.usuario = User.objects.create_user(
            username="administrador",
            password="PruebaSegura2026!"
        )

        self.inmueble = Inmueble.objects.create(
            java_id=999991,
            titulo="Casa de prueba",
            descripcion="Inmueble creado para pruebas",
            precio=Decimal("150000000"),
            direccion="Calle de prueba 123",
            ciudad="Bogotá",
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

    def test_solo_los_inmuebles_aprobados_se_publican(self):
        publicaciones = Inmueble.objects.filter(
            estado_revision=Inmueble.EstadoRevision.APROBADA
        )

        self.assertEqual(publicaciones.count(), 0)

        self.inmueble.estado_revision = Inmueble.EstadoRevision.APROBADA
        self.inmueble.save()

        publicaciones = Inmueble.objects.filter(
            estado_revision=Inmueble.EstadoRevision.APROBADA
        )

        self.assertEqual(publicaciones.count(), 1)

    def test_rechazo_debe_tener_observacion(self):
        self.inmueble.estado_revision = Inmueble.EstadoRevision.RECHAZADA
        self.inmueble.observacion_revision = ""

        with self.assertRaises(Exception):
            self.inmueble.full_clean()
