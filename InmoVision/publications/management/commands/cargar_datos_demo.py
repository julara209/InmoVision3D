from decimal import Decimal

from django.contrib.auth import get_user_model
from django.core.management import call_command
from django.core.management.base import BaseCommand
from django.utils import timezone

from publications.models import Inmueble


class Command(BaseCommand):
    help = "Carga inmuebles de demostración para probar el módulo de aprobación."

    def handle(self, *args, **options):

        # Los inmuebles de demostración pertenecen al publicador demo.
        call_command("crear_usuarios_demo", stdout=self.stdout)
        publicador = get_user_model().objects.get(
            username="publicador@demo.com"
        )

        datos = [
            {
                "java_id": 990001,
                "titulo": "Casa familiar en Bogotá",
                "descripcion": "Casa de demostración con tres habitaciones y patio.",
                "precio": Decimal("280000000"),
                "direccion": "Calle 100 # 15-20",
                "ciudad": "Bogotá",
                "tipo": Inmueble.TipoInmueble.CASA,
                "operacion": Inmueble.TipoOperacion.VENTA,
                "estado_revision": Inmueble.EstadoRevision.PENDIENTE,
                "observacion_revision": "",
            },
            {
                "java_id": 990002,
                "titulo": "Apartamento en Chía",
                "descripcion": "Apartamento de demostración con dos habitaciones.",
                "precio": Decimal("190000000"),
                "direccion": "Carrera 10 # 12-30",
                "ciudad": "Chía",
                "tipo": Inmueble.TipoInmueble.APARTAMENTO,
                "operacion": Inmueble.TipoOperacion.VENTA,
                "estado_revision": Inmueble.EstadoRevision.APROBADA,
                "observacion_revision": "",
            },
            {
                "java_id": 990003,
                "titulo": "Local comercial en Soacha",
                "descripcion": "Local comercial de demostración.",
                "precio": Decimal("1800000"),
                "direccion": "Calle 13 # 7-45",
                "ciudad": "Soacha",
                "tipo": Inmueble.TipoInmueble.LOCAL_COMERCIAL,
                "operacion": Inmueble.TipoOperacion.ARRIENDO,
                "estado_revision": Inmueble.EstadoRevision.RECHAZADA,
                "observacion_revision": "Debe corregir la descripción y completar la información.",
            },
            {
                "java_id": 990004,
                "titulo": "Terreno en Cajicá",
                "descripcion": "Terreno de demostración para uso residencial.",
                "precio": Decimal("320000000"),
                "direccion": "Vereda Canelón",
                "ciudad": "Cajicá",
                "tipo": Inmueble.TipoInmueble.TERRENO,
                "operacion": Inmueble.TipoOperacion.VENTA,
                "estado_revision": Inmueble.EstadoRevision.DEVUELTA,
                "observacion_revision": "Debe agregar información sobre el área del terreno.",
            },
        ]

        for dato in datos:
            inmueble, creado = Inmueble.objects.update_or_create(
                java_id=dato["java_id"],
                defaults={
                    **dato,
                    "publicador": publicador,
                    "propietario_java_id": 1,
                    "propietario_nombre": "Usuario",
                    "propietario_apellido": "Demostración",
                    "propietario_email": "demo@example.com",
                    "propietario_telefono": "3000000000",
                }
            )

            if creado:
                self.stdout.write(
                    self.style.SUCCESS(
                        f"Inmueble creado: {inmueble.titulo}"
                    )
                )
            else:
                self.stdout.write(
                    self.style.WARNING(
                        f"Inmueble actualizado: {inmueble.titulo}"
                    )
                )

        self.stdout.write(
            self.style.SUCCESS(
                "Carga de datos de demostración finalizada."
            )
        )
