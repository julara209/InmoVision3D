"""
Panel de administración. Las tablas que pertenecen a la aplicación Java se muestran en SOLO LECTURA
(sin crear, editar ni borrar) para no dañar los datos del sistema principal.
Lo único editable es la ubicación en el mapa de cada inmueble (tabla propia de Django).
"""
from django.contrib import admin
from django.db.models import BooleanField, Case, Q, Value, When

from .models import Favorito, ImagenInmueble, Inmueble, Plano2D, Solicitud, UbicacionInmueble, Usuario

admin.site.site_header = "InmoVision 3D — Administración"
admin.site.site_title = "InmoVision 3D"
admin.site.index_title = "Datos de la aplicación"


class SoloLecturaAdmin(admin.ModelAdmin):
    list_per_page = 50

    def has_add_permission(self, request):
        return False

    def has_change_permission(self, request, obj=None):
        return False

    def has_delete_permission(self, request, obj=None):
        return False


class UbicacionInline(admin.StackedInline):
    model = UbicacionInmueble
    fields = ("latitud", "longitud")
    extra = 1
    max_num = 1
    can_delete = True
    verbose_name = "ubicación en el mapa"
    verbose_name_plural = "ubicación en el mapa"


@admin.register(Inmueble)
class InmuebleAdmin(admin.ModelAdmin):
    """Datos del inmueble en solo lectura; la ubicación en el mapa se edita en el bloque de abajo."""

    list_display = ("id", "titulo", "tipo", "operacion", "precio", "ciudad", "estado", "propietario", "tiene_ubicacion")
    list_filter = ("estado", "tipo", "operacion", "ciudad")
    search_fields = ("titulo", "direccion", "ciudad")
    list_select_related = ("propietario", "ubicacion")
    list_per_page = 50
    inlines = [UbicacionInline]

    def get_readonly_fields(self, request, obj=None):
        return [campo.name for campo in self.model._meta.fields]

    def has_add_permission(self, request):
        return False

    def has_delete_permission(self, request, obj=None):
        return False

    def save_model(self, request, obj, form, change):
        # No se escribe NADA en la tabla `inmuebles` de Java; solo se guarda el bloque de ubicación.
        pass

    @admin.display(boolean=True, description="Ubicado en el mapa")
    def tiene_ubicacion(self, obj):
        return hasattr(obj, "ubicacion")


@admin.register(Usuario)
class UsuarioAdmin(SoloLecturaAdmin):
    list_display = ("id", "nombre", "apellido", "email", "rol", "activo", "fecha_registro")
    list_filter = ("rol", "activo")
    search_fields = ("nombre", "apellido", "email")
    exclude = ("password",)  # el hash de la contraseña nunca se muestra


@admin.register(ImagenInmueble)
class ImagenInmuebleAdmin(SoloLecturaAdmin):
    list_display = ("id", "inmueble", "url", "principal")
    list_select_related = ("inmueble",)
    list_filter = ("principal",)


@admin.register(Plano2D)
class Plano2DAdmin(SoloLecturaAdmin):
    list_display = ("id", "nombre", "inmueble", "piso", "tiene_3d")
    list_select_related = ("inmueble",)
    exclude = ("datos_3d",)  # el JSON del modelo 3D es muy pesado para mostrarlo

    def get_queryset(self, request):
        return (
            super().get_queryset(request)
            .defer("datos_3d")
            .annotate(modelo3d_ok=Case(
                When(Q(datos_3d__isnull=False) & ~Q(datos_3d=""), then=Value(True)),
                default=Value(False),
                output_field=BooleanField(),
            ))
        )

    @admin.display(boolean=True, description="Tiene modelo 3D")
    def tiene_3d(self, obj):
        return obj.modelo3d_ok


@admin.register(Favorito)
class FavoritoAdmin(SoloLecturaAdmin):
    list_display = ("id", "usuario", "inmueble", "fecha_agregado")
    list_select_related = ("usuario", "inmueble")


@admin.register(Solicitud)
class SolicitudAdmin(SoloLecturaAdmin):
    list_display = ("id", "usuario", "inmueble", "estado", "fecha_solicitud", "fecha_cita")
    list_select_related = ("usuario", "inmueble")
    list_filter = ("estado",)
