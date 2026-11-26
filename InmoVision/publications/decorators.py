from functools import wraps

from django.contrib.auth.views import redirect_to_login
from django.shortcuts import render
from django.views.decorators.cache import never_cache

from .roles import rol_de


def rol_requerido(*roles):
    """
    Protege una vista:

    * Sin sesión -> redirige al login.
    * Con sesión pero sin el rol adecuado -> página de acceso denegado (403).
    * Siempre añade cabeceras para que el navegador no guarde la página,
      así al cerrar sesión el botón "atrás" no muestra contenido privado.
    """

    def decorador(vista):
        @wraps(vista)
        def envoltura(request, *args, **kwargs):
            if not request.user.is_authenticated:
                return redirect_to_login(request.get_full_path())

            if rol_de(request.user) not in roles:
                return render(
                    request,
                    "publications/acceso_denegado.html",
                    status=403,
                )

            return vista(request, *args, **kwargs)

        return never_cache(envoltura)

    return decorador
