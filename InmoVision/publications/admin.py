
from django import forms
from django.contrib import admin, messages
from django.core.exceptions import PermissionDenied
from django.utils import timezone

from .models import (
    Inmueble,
    ImagenInmueble,
    Plano2D,
    TipoInmuebleCatalogo,
    Modalidad,
    Ubicacion,
)
from .roles import es_administrador


class InmuebleAdminForm(forms.ModelForm):

    class Meta:
        model = Inmueble
        fields = "__all__"

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

        mensajes = {
            "java_id": "El identificador del inmueble es obligatorio.",
            "titulo": "El título del inmueble es obligatorio.",
            "precio": "El precio del inmueble es obligatorio.",
            "direccion": "La dirección del inmueble es obligatoria.",
            "tipo": "Selecciona el tipo de inmueble.",
            "operacion": "Selecciona la modalidad.",
        }

        for campo, mensaje in mensajes.items():
            if campo in self.fields:
                self.fields[campo].error_messages["required"] = mensaje

        self.fields["precio"].error_messages["invalid"] = (
            "Ingresa un precio válido."
        )

        # Catálogo de tipos de inmueble
        tipos = TipoInmuebleCatalogo.objects.all()

        self.fields["tipo"].choices = [
            ("", "Selecciona un tipo de inmueble")
        ] + [
            (tipo.codigo, tipo.nombre)
            for tipo in tipos
        ]

        # Catálogo de modalidades
        modalidades = Modalidad.objects.all()

        self.fields["operacion"].choices = [
            ("", "Selecciona una modalidad")
        ] + [
            (modalidad.codigo, modalidad.nombre)
            for modalidad in modalidades
        ]

        # Catálogo de ciudades
        ubicaciones = Ubicacion.objects.all()

        self.fields["ciudad"].widget = forms.Select(
            choices=[
                ("", "Selecciona una ciudad")
            ] + [
                (ubicacion.nombre, ubicacion.nombre)
                for ubicacion in ubicaciones
            ]
        )

        self.fields["ciudad"].required = False

    def clean_titulo(self):
        titulo = self.cleaned_data.get("titulo", "").strip()

        if not titulo:
            raise forms.ValidationError(
                "El título del inmueble es obligatorio."
            )

        return titulo

    def clean_direccion(self):
        direccion = self.cleaned_data.get("direccion", "").strip()

        if not direccion:
            raise forms.ValidationError(
                "La dirección del inmueble es obligatoria."
            )

        return direccion

    def clean_precio(self):
        precio = self.cleaned_data.get("precio")

        if precio is not None and precio <= 0:
            raise forms.ValidationError(
                "El precio debe ser mayor que cero."
            )

        return precio

    def clean(self):
        cleaned_data = super().clean()

        area = cleaned_data.get("area")
        habitaciones = cleaned_data.get("habitaciones")
        banos = cleaned_data.get("banos")

        if area is not None and area < 0:
            self.add_error(
                "area",
                "El área no puede ser negativa."
            )

        if habitaciones is not None and habitaciones < 0:
            self.add_error(
                "habitaciones",
                "La cantidad de habitaciones no puede ser negativa."
            )

        if banos is not None and banos < 0:
            self.add_error(
                "banos",
                "La cantidad de baños no puede ser negativa."
            )

        return cleaned_data


class ImagenInmuebleInline(admin.TabularInline):
    model = ImagenInmueble
    extra = 0


class Plano2DInline(admin.TabularInline):
    model = Plano2D
    extra = 0


@admin.register(Inmueble)
class InmuebleAdmin(admin.ModelAdmin):
    form = InmuebleAdminForm

    list_display = (
        "titulo",
        "java_id",
        "tipo",
        "operacion",
        "estado_revision",
        "publicador",
        "fecha_recepcion",
        "administrador_revisor",
    )

    list_filter = (
        "estado_revision",
        "publicador",
        "tipo",
        "operacion",
        "estado_comercial",
    )

    search_fields = (
        "titulo",
        "direccion",
        "ciudad",
        "propietario_nombre",
        "propietario_email",
    )

    readonly_fields = (
        "fecha_recepcion",
        "fecha_revision",
        "administrador_revisor",
    )

    inlines = [
        ImagenInmuebleInline,
        Plano2DInline,
    ]

    actions = [
        "aprobar_publicaciones",
        "rechazar_publicaciones",
        "devolver_publicaciones",
    ]

    # ------------------------------------------------------------------
    # HU-003: solo el rol Administrador revisa publicaciones.
    # Un publicador (o un cliente) no ve ni modifica nada de este módulo,
    # aunque por error tuviera acceso al sitio de administración.
    # ------------------------------------------------------------------
    def has_module_permission(self, request):
        return es_administrador(request.user) and super().has_module_permission(request)

    def has_view_permission(self, request, obj=None):
        return es_administrador(request.user) and super().has_view_permission(request, obj)

    def has_change_permission(self, request, obj=None):
        return es_administrador(request.user) and super().has_change_permission(request, obj)

    def has_add_permission(self, request):
        return es_administrador(request.user) and super().has_add_permission(request)

    def has_delete_permission(self, request, obj=None):
        return es_administrador(request.user) and super().has_delete_permission(request, obj)

    def get_actions(self, request):
        if not es_administrador(request.user):
            return {}
        return super().get_actions(request)

    def registrar_revision(self, request, queryset, estado):
        """
        Guarda la decisión del administrador. Devuelve False (sin cambiar
        nada) si algún inmueble seleccionado es del propio revisor: nadie
        aprueba, rechaza ni devuelve sus propias publicaciones.
        """
        if not es_administrador(request.user):
            raise PermissionDenied

        if queryset.filter(publicador=request.user).exists():
            self.message_user(
                request,
                "No puedes revisar tus propias publicaciones.",
                messages.ERROR,
            )
            return False

        for inmueble in queryset:
            inmueble.estado_revision = estado
            inmueble.administrador_revisor = request.user
            inmueble.fecha_revision = timezone.now()

            inmueble.save(
                update_fields=[
                    "estado_revision",
                    "administrador_revisor",
                    "fecha_revision",
                ]
            )

        return True

    @admin.action(description="Aprobar publicaciones seleccionadas")
    def aprobar_publicaciones(self, request, queryset):
        cantidad = queryset.count()

        if not self.registrar_revision(
            request,
            queryset,
            Inmueble.EstadoRevision.APROBADA,
        ):
            return

        self.message_user(
            request,
            f"{cantidad} publicación(es) aprobada(s).",
            messages.SUCCESS,
        )

    @admin.action(description="Rechazar publicaciones seleccionadas")
    def rechazar_publicaciones(self, request, queryset):
        sin_observacion = [
            inmueble.titulo
            for inmueble in queryset
            if not inmueble.observacion_revision.strip()
        ]

        if sin_observacion:
            self.message_user(
                request,
                "No se puede rechazar: escribe una observación para cada publicación seleccionada.",
                messages.ERROR,
            )
            return

        cantidad = queryset.count()

        if not self.registrar_revision(
            request,
            queryset,
            Inmueble.EstadoRevision.RECHAZADA,
        ):
            return

        self.message_user(
            request,
            f"{cantidad} publicación(es) rechazada(s).",
            messages.SUCCESS,
        )

    @admin.action(description="Devolver publicaciones seleccionadas")
    def devolver_publicaciones(self, request, queryset):
        sin_observacion = [
            inmueble.titulo
            for inmueble in queryset
            if not inmueble.observacion_revision.strip()
        ]

        if sin_observacion:
            self.message_user(
                request,
                "No se puede devolver: escribe una observación para cada publicación seleccionada.",
                messages.ERROR,
            )
            return

        cantidad = queryset.count()

        if not self.registrar_revision(
            request,
            queryset,
            Inmueble.EstadoRevision.DEVUELTA,
        ):
            return

        self.message_user(
            request,
            f"{cantidad} publicación(es) devuelta(s).",
            messages.SUCCESS,
        )


@admin.register(TipoInmuebleCatalogo)
class TipoInmuebleCatalogoAdmin(admin.ModelAdmin):
    list_display = ("codigo", "nombre")
    search_fields = ("codigo", "nombre")


@admin.register(Modalidad)
class ModalidadAdmin(admin.ModelAdmin):
    list_display = ("codigo", "nombre")
    search_fields = ("codigo", "nombre")


@admin.register(Ubicacion)
class UbicacionAdmin(admin.ModelAdmin):
    list_display = ("codigo", "nombre")
    search_fields = ("codigo", "nombre")
