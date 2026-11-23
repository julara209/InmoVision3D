
from decimal import Decimal

from django.core.exceptions import ValidationError
from django.test import TestCase

from .models import Inmueble, obtener_inmuebles_publicados


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
