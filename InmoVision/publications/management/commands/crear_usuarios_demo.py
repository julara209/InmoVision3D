from django.contrib.auth import get_user_model
from django.core.management.base import BaseCommand

from publications.roles import (
    GRUPO_ADMINISTRADOR,
    GRUPO_CLIENTE,
    GRUPO_PUBLICADOR,
    crear_grupos_y_permisos,
)

CONTRASENA_DEMO = "Demo12345!"

USUARIOS_DEMO = [
    # (correo, nombre, grupo, es_staff)
    ("admin@demo.com", "Admin Demo", GRUPO_ADMINISTRADOR, True),
    ("publicador@demo.com", "Publicador Demo", GRUPO_PUBLICADOR, False),
    ("publicador2@demo.com", "Publicador Dos", GRUPO_PUBLICADOR, False),
    ("cliente@demo.com", "Cliente Demo", GRUPO_CLIENTE, False),
]


class Command(BaseCommand):
    help = (
        "Crea un usuario de demostración por rol (y un segundo publicador). "
        "Solo para pruebas: no usar en un entorno real."
    )

    def handle(self, *args, **options):
        User = get_user_model()
        grupos = {g.name: g for g in crear_grupos_y_permisos()}

        for correo, nombre, nombre_grupo, es_staff in USUARIOS_DEMO:
            usuario, creado = User.objects.get_or_create(
                username=correo,
                defaults={
                    "email": correo,
                    "first_name": nombre,
                    "is_staff": es_staff,
                },
            )

            if creado:
                usuario.set_password(CONTRASENA_DEMO)
                usuario.save()

            usuario.groups.add(grupos[nombre_grupo])

            self.stdout.write(
                f"{nombre_grupo}: {correo} "
                f"({'creado' if creado else 'ya existía'})."
            )

        self.stdout.write(
            self.style.SUCCESS(
                f"Usuarios de demostración listos. Contraseña: {CONTRASENA_DEMO}"
            )
        )
