from django.apps import AppConfig
from django.db.models.signals import post_migrate


def _crear_roles(sender, **kwargs):
    from .roles import crear_grupos_y_permisos

    crear_grupos_y_permisos()


class PublicationsConfig(AppConfig):
    name = 'publications'

    def ready(self):
        # Después de cada `migrate` quedan creados los grupos
        # Administrador, Publicador y Cliente con sus permisos.
        post_migrate.connect(
            _crear_roles,
            sender=self,
            dispatch_uid="publications_crear_roles",
        )
