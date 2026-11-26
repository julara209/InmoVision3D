from .roles import rol_de


def rol_actual(request):
    """Pone la variable `rol` disponible en todas las plantillas."""
    usuario = getattr(request, "user", None)

    if usuario is not None and usuario.is_authenticated:
        return {"rol": rol_de(usuario)}

    return {"rol": None}
