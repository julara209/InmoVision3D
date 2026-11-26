from django.conf import settings
from django.db import DatabaseError
from django.db.models import BooleanField, Case, Prefetch, Q, Value, When
from django.http import JsonResponse
from django.shortcuts import render
from django.views.decorators.clickjacking import xframe_options_exempt

from .models import ImagenInmueble, Inmueble, Plano2D

# Valores válidos (iguales a los enum de Java); evitan filtrar con texto arbitrario
TIPOS = {"CASA", "APARTAMENTO", "LOCAL_COMERCIAL", "TERRENO", "OFICINA", "BODEGA"}
OPERACIONES = {"VENTA", "ARRIENDO"}


@xframe_options_exempt  # permite incrustar /mapa/?embed=1 en un <iframe> de la aplicación Java
def mapa(request):
    return render(request, "core/mapa.html", {
        "spring_url": settings.SPRING_BASE_URL,
        "embed": request.GET.get("embed") == "1",   # oculta la barra superior
    })


def _absoluta(ruta):
    """Las rutas de imagen de Java son relativas (/uploads/...): se completan con su dominio."""
    if not ruta:
        return ""
    if ruta.startswith(("http://", "https://")):
        return ruta
    return settings.SPRING_BASE_URL + (ruta if ruta.startswith("/") else "/" + ruta)


def _imagen_principal(inmueble):
    """Misma regla que Java: la marcada como principal; si no hay, la primera; si no, el logo."""
    imagenes = list(inmueble.imagenes.all())  # precargadas y ordenadas
    elegida = next((i for i in imagenes if i.principal), imagenes[0] if imagenes else None)
    return _absoluta(elegida.url if elegida else "/img/logo.png")


def api_inmuebles(request):
    """GET /api/inmuebles/?tipo=CASA&operacion=VENTA -> inmuebles DISPONIBLES que ya tienen ubicación."""
    tipo = request.GET.get("tipo", "").strip().upper()
    operacion = request.GET.get("operacion", "").strip().upper()

    try:
        disponibles = Inmueble.objects.filter(estado="DISPONIBLE")

        qs = (
            disponibles.filter(ubicacion__isnull=False)
            .select_related("ubicacion")
            .prefetch_related(Prefetch("imagenes", queryset=ImagenInmueble.objects.order_by("id")))
            .order_by("-id")
        )
        if tipo in TIPOS:
            qs = qs.filter(tipo=tipo)
        if operacion in OPERACIONES:
            qs = qs.filter(operacion=operacion)

        lista = list(qs)

        # Plano 2D y modelo 3D: Java usa SIEMPRE el primer plano del inmueble (planos.get(0)),
        # así que aquí se aplica la misma regla. No se descarga el JSON pesado del modelo 3D.
        primer_plano = {}
        planos = (
            Plano2D.objects.filter(inmueble_id__in=[i.id for i in lista])
            .annotate(modelo3d_ok=Case(
                When(Q(datos_3d__isnull=False) & ~Q(datos_3d=""), then=Value(True)),
                default=Value(False),
                output_field=BooleanField(),
            ))
            .order_by("inmueble_id", "id")
            .values("inmueble_id", "nombre", "url", "modelo3d_ok")
        )
        for pl in planos:
            primer_plano.setdefault(pl["inmueble_id"], pl)

        inmuebles = []
        for i in lista:
            plano = primer_plano.get(i.id)
            inmuebles.append({
                "id": i.id,
                "titulo": i.titulo,
                "tipo": i.tipo,
                "operacion": i.operacion,
                "precio": float(i.precio),
                "direccion": i.direccion,
                "ciudad": i.ciudad or "",
                "habitaciones": i.habitaciones or 0,
                "banos": i.banos or 0,
                "area": i.area or 0,
                "lat": i.ubicacion.latitud,
                "lng": i.ubicacion.longitud,
                "imagen": _imagen_principal(i),
                "url": f"{settings.SPRING_BASE_URL}/inmuebles/{i.id}",
                # Plano 2D: archivo del plano (público en /uploads/...). "" si el inmueble no tiene plano.
                "plano_2d_url": _absoluta(plano["url"]) if plano else "",
                # Visor 3D: solo disponible si el primer plano ya tiene modelo 3D generado.
                "tiene_3d": bool(plano and plano["modelo3d_ok"]),
                "url_3d": f"{settings.SPRING_BASE_URL}/planos/visor3d/{i.id}",
            })

        sin_ubicacion = disponibles.filter(ubicacion__isnull=True).count()
    except DatabaseError:
        return JsonResponse({"error": "No se pudo leer la base de datos de InmoVision 3D."}, status=503)

    return JsonResponse({"inmuebles": inmuebles, "sin_ubicacion": sin_ubicacion})
