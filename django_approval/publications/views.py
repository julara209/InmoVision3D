
from django.shortcuts import render
from django.contrib.auth.decorators import login_required

from .models import Inmueble


@login_required
def mis_observaciones(request):
    inmuebles = Inmueble.objects.filter(
        propietario_java_id=request.user.id,
        estado_revision__in=[
            Inmueble.EstadoRevision.RECHAZADA,
            Inmueble.EstadoRevision.DEVUELTA,
        ]
    ).order_by("-fecha_revision")

    return render(
        request,
        "publications/mis_observaciones.html",
        {"inmuebles": inmuebles}
    )
