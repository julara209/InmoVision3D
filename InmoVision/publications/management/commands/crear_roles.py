from django.core.management.base import BaseCommand

from publications.roles import crear_grupos_y_permisos


class Command(BaseCommand):
    help = (
        "Crea los grupos Administrador, Publicador y Cliente con sus "
        "permisos. Se puede ejecutar varias veces sin duplicar nada."
    )

    def handle(self, *args, **options):
        grupos = crear_grupos_y_permisos()

        for grupo in grupos:
            permisos = grupo.permissions.count()
            self.stdout.write(f"Grupo {grupo.name}: {permisos} permiso(s).")

        self.stdout.write(
            self.style.SUCCESS("Roles de InmoVision 3D listos.")
        )
