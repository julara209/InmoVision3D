import csv
import io

from django.contrib import messages
from django.contrib.auth.views import LoginView
from django.db import transaction
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.cache import never_cache

from .decorators import rol_requerido
from .forms import CargaCSVForm, InicioSesionForm, RegistroClienteForm
from .models import Inmueble, obtener_inmuebles_publicados
from .roles import (
    ROL_ADMINISTRADOR,
    ROL_CLIENTE,
    ROL_PUBLICADOR,
    rol_de,
)

TODOS_LOS_ROLES = (ROL_ADMINISTRADOR, ROL_PUBLICADOR, ROL_CLIENTE)


# ---------------------------------------------------------------------------
# Acceso: login, registro y redirección según el rol
# ---------------------------------------------------------------------------

class InicioSesionView(LoginView):
    """Login con correo y contraseña. El logout se hace con POST (ver urls)."""

    template_name = "publications/login.html"
    authentication_form = InicioSesionForm
    redirect_authenticated_user = True


@never_cache
def registro_cliente(request):
    """Registro público. Solo crea cuentas con rol Cliente."""

    if request.user.is_authenticated:
        return redirect("inicio")

    if request.method == "POST":
        form = RegistroClienteForm(request.POST)

        if form.is_valid():
            form.save()
            messages.success(
                request,
                "Tu cuenta fue creada correctamente. Ya puedes iniciar sesión."
            )
            return redirect("login")
    else:
        form = RegistroClienteForm()

    return render(request, "publications/registro.html", {"form": form})


@never_cache
def inicio(request):
    """Después de ingresar, cada rol llega a su propio panel."""

    if not request.user.is_authenticated:
        return redirect("login")

    rol = rol_de(request.user)

    if rol == ROL_ADMINISTRADOR:
        return redirect("admin:index")

    if rol == ROL_PUBLICADOR:
        return redirect("panel_publicador")

    if rol == ROL_CLIENTE:
        return redirect("catalogo")

    # Usuario sin ningún rol asignado.
    return render(
        request,
        "publications/acceso_denegado.html",
        {"sin_rol": True},
        status=403
    )


# ---------------------------------------------------------------------------
# Publicador: solo ve y abre sus propias propiedades
# ---------------------------------------------------------------------------

@rol_requerido(ROL_PUBLICADOR)
def panel_publicador(request):
    inmuebles = Inmueble.objects.filter(publicador=request.user)

    return render(
        request,
        "publications/panel_publicador.html",
        {"inmuebles": inmuebles}
    )


@rol_requerido(ROL_PUBLICADOR)
def detalle_inmueble_propio(request, pk):
    # Si la propiedad es de otro publicador responde 404, igual que si no
    # existiera: así tampoco se revela que existe.
    inmueble = get_object_or_404(Inmueble, pk=pk, publicador=request.user)

    return render(
        request,
        "publications/detalle_inmueble.html",
        {"inmueble": inmueble, "es_propio": True}
    )


@rol_requerido(ROL_PUBLICADOR)
def mis_observaciones(request):
    inmuebles = Inmueble.objects.filter(
        publicador=request.user,
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


# ---------------------------------------------------------------------------
# Catálogo: cualquier usuario con sesión consulta solo inmuebles aprobados
# ---------------------------------------------------------------------------

@rol_requerido(*TODOS_LOS_ROLES)
def catalogo(request):
    return render(
        request,
        "publications/catalogo.html",
        {"inmuebles": obtener_inmuebles_publicados()}
    )


@rol_requerido(*TODOS_LOS_ROLES)
def detalle_inmueble_publico(request, pk):
    # Solo se pueden abrir inmuebles aprobados.
    inmueble = get_object_or_404(obtener_inmuebles_publicados(), pk=pk)

    return render(
        request,
        "publications/detalle_inmueble.html",
        {"inmueble": inmueble, "es_propio": False}
    )


# ---------------------------------------------------------------------------
# Carga masiva (solo publicadores)
# ---------------------------------------------------------------------------

@rol_requerido(ROL_PUBLICADOR)
def cargar_inmuebles_csv(request):
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
                                publicador=request.user,
                                propietario_nombre=request.user.first_name,
                                propietario_email=request.user.email,
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
