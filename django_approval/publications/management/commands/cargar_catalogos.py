
from django.core.management.base import BaseCommand

from publications.models import (
    TipoInmuebleCatalogo,
    Modalidad,
    Ubicacion,
)


class Command(BaseCommand):
    help = "Carga los catálogos iniciales de InmoVision 3D."

    def handle(self, *args, **options):

        tipos = [
            ("CASA", "Casa"),
            ("APARTAMENTO", "Apartamento"),
            ("LOCAL_COMERCIAL", "Local comercial"),
            ("TERRENO", "Terreno"),
            ("OFICINA", "Oficina"),
            ("BODEGA", "Bodega"),
        ]

        modalidades = [
            ("VENTA", "Venta"),
            ("ARRIENDO", "Arriendo"),
        ]

        ubicaciones = [
            ("BOGOTA", "Bogotá"),
            ("CAJICA", "Cajicá"),
            ("CHIA", "Chía"),
            ("SOACHA", "Soacha"),
            ("ZIPAQUIRA", "Zipaquirá"),
        ]

        for codigo, nombre in tipos:
            _, creado = TipoInmuebleCatalogo.objects.get_or_create(
                codigo=codigo,
                defaults={"nombre": nombre}
            )
            self.stdout.write(
                f"Tipo de inmueble: {nombre} "
                f"{'creado' if creado else 'ya existía'}."
            )

        for codigo, nombre in modalidades:
            _, creado = Modalidad.objects.get_or_create(
                codigo=codigo,
                defaults={"nombre": nombre}
            )
            self.stdout.write(
                f"Modalidad: {nombre} "
                f"{'creada' if creado else 'ya existía'}."
            )

        for codigo, nombre in ubicaciones:
            _, creado = Ubicacion.objects.get_or_create(
                codigo=codigo,
                defaults={"nombre": nombre}
            )
            self.stdout.write(
                f"Ubicación: {nombre} "
                f"{'creada' if creado else 'ya existía'}."
            )

        self.stdout.write(
            self.style.SUCCESS(
                "Catálogos de InmoVision 3D cargados correctamente."
            )
        )
