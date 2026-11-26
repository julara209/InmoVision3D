from django.contrib import admin

from .models import (
    FavoritoJava,
    ImagenInmuebleJava,
    InmuebleJava,
    Plano2DJava,
    SolicitudJava,
    UsuarioJava,
)


class SoloLecturaAdmin(admin.ModelAdmin):
    """
    Estas tablas son de la aplicación Java: desde Django solo se consultan.
    No se puede agregar, editar ni borrar nada.
    """

    list_per_page = 25

    def has_add_permission(self, request):
        return False

    def has_change_permission(self, request, obj=None):
        return False

    def has_delete_permission(self, request, obj=None):
        return False


class ImagenInline(admin.TabularInline):
    model = ImagenInmuebleJava
    extra = 0
    max_num = 0
    can_delete = False
    fields = ("url", "descripcion", "principal")
    readonly_fields = fields

    def has_add_permission(self, request, obj=None):
        return False

    def has_change_permission(self, request, obj=None):
        return False

    def has_delete_permission(self, request, obj=None):
        return False


class PlanoInline(ImagenInline):
    model = Plano2DJava
    fields = ("nombre", "numero_piso", "url")
    readonly_fields = fields


@admin.register(InmuebleJava)
class InmuebleJavaAdmin(SoloLecturaAdmin):
    list_display = (
        "id",
        "titulo",
        "tipo",
        "operacion",
        "ciudad",
        "precio",
        "estado",
        "estado_publicacion",
        "propietario",
        "fecha_publicacion",
    )
    list_filter = ("estado_publicacion", "tipo", "operacion", "estado", "ciudad")
    search_fields = ("titulo", "direccion", "ciudad", "propietario__email")
    list_select_related = ("propietario",)
    inlines = [ImagenInline, PlanoInline]


@admin.register(UsuarioJava)
class UsuarioJavaAdmin(SoloLecturaAdmin):
    list_display = ("id", "nombre", "apellido", "email", "rol", "activo", "fecha_registro")
    list_filter = ("rol", "activo")
    search_fields = ("nombre", "apellido", "email")
    # El hash de la contraseña nunca se muestra en el panel.
    exclude = ("password",)


@admin.register(ImagenInmuebleJava)
class ImagenInmuebleJavaAdmin(SoloLecturaAdmin):
    list_display = ("id", "inmueble", "url", "principal")
    list_select_related = ("inmueble",)


@admin.register(Plano2DJava)
class Plano2DJavaAdmin(SoloLecturaAdmin):
    list_display = ("id", "inmueble", "nombre", "numero_piso", "url")
    list_select_related = ("inmueble",)


@admin.register(FavoritoJava)
class FavoritoJavaAdmin(SoloLecturaAdmin):
    list_display = ("id", "usuario", "inmueble", "fecha_agregado")
    list_select_related = ("usuario", "inmueble")


@admin.register(SolicitudJava)
class SolicitudJavaAdmin(SoloLecturaAdmin):
    list_display = ("id", "usuario", "inmueble", "estado", "fecha_solicitud", "fecha_cita")
    list_filter = ("estado",)
    list_select_related = ("usuario", "inmueble")
