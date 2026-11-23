import csv
import io

from django.shortcuts import render
from django.contrib.auth.decorators import login_required
from django.db import transaction

from .models import Inmueble
from .forms import CargaCSVForm


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


@login_required
def cargar_inmuebles_csv(request):
    if not (
        request.user.is_superuser
        or request.user.groups.filter(name="Publicador").exists()
    ):
        return render(
            request,
            "publications/cargar_csv.html",
            {
                "form": CargaCSVForm(),
                "errores": ["No tienes permiso para cargar inmuebles."]
            },
            status=403
        )

    errores = []
    creados = 0

    if request.method == "POST":
        form = CargaCSVForm(request.POST, request.FILES)

        if form.is_valid():
            archivo = form.cleaned_data["archivo"]

            try:
                contenido = archivo.read().decode("utf-8-sig")
                lector = csv.DictReader(io.StringIO(contenido))

                requeridas = {
                    "java_id", "titulo", "descripcion", "precio",
                    "direccion", "ciudad", "tipo", "operacion",
                    "estado_comercial", "area", "habitaciones", "banos"
                }

                if not lector.fieldnames or not requeridas.issubset(
                    set(lector.fieldnames)
                ):
                    errores.append("El CSV no contiene todas las columnas requeridas.")
                else:
                    inmuebles = []
                    ids_archivo = set()

                    for numero, fila in enumerate(lector, start=2):
                        try:
                            java_id = int(fila["java_id"])

                            if java_id in ids_archivo:
                                raise ValueError("El ID está repetido en el archivo.")

                            ids_archivo.add(java_id)

                            inmueble = Inmueble(
                                java_id=java_id,
                                titulo=fila["titulo"].strip(),
                                descripcion=fila["descripcion"].strip(),
                                precio=fila["precio"],
                                direccion=fila["direccion"].strip(),
                                ciudad=fila["ciudad"].strip(),
                                tipo=fila["tipo"].strip(),
                                operacion=fila["operacion"].strip(),
                                estado_comercial=fila["estado_comercial"].strip(),
                                area=float(fila["area"]) if fila["area"].strip() else None,
                                habitaciones=int(fila["habitaciones"]) if fila["habitaciones"].strip() else None,
                                banos=int(fila["banos"]) if fila["banos"].strip() else None,
                                propietario_java_id=request.user.id,
                                estado_revision=Inmueble.EstadoRevision.PENDIENTE
                            )

                            inmueble.full_clean()
                            inmuebles.append(inmueble)

                        except Exception as error:
                            errores.append(f"Fila {numero}: {error}")

                    if not errores:
                        try:
                            with transaction.atomic():
                                for inmueble in inmuebles:
                                    inmueble.save()
                            creados = len(inmuebles)
                        except Exception as error:
                            errores.append(f"No se pudieron guardar los inmuebles: {error}")

            except (UnicodeDecodeError, csv.Error) as error:
                errores.append(f"No se pudo leer el archivo CSV: {error}")

    else:
        form = CargaCSVForm()

    return render(
        request,
        "publications/cargar_csv.html",
        {
            "form": form,
            "errores": errores,
            "creados": creados
        }
    )
