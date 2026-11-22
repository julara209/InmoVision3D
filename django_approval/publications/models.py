
from django.conf import settings
from django.core.exceptions import ValidationError
from django.db import models


class TipoInmuebleCatalogo(models.Model):
    codigo = models.CharField(max_length=30, unique=True)
    nombre = models.CharField(max_length=100, unique=True)

    class Meta:
        verbose_name = "Tipo de inmueble"
        verbose_name_plural = "Tipos de inmueble"
        ordering = ["nombre"]

    def __str__(self):
        return self.nombre


class Modalidad(models.Model):
    codigo = models.CharField(max_length=20, unique=True)
    nombre = models.CharField(max_length=100, unique=True)

    class Meta:
        verbose_name = "Modalidad"
        verbose_name_plural = "Modalidades"
        ordering = ["nombre"]

    def __str__(self):
        return self.nombre


class Ubicacion(models.Model):
    codigo = models.CharField(max_length=50, unique=True)
    nombre = models.CharField(max_length=100, unique=True)

    class Meta:
        verbose_name = "Ubicación"
        verbose_name_plural = "Ubicaciones"
        ordering = ["nombre"]

    def __str__(self):
        return self.nombre


class Inmueble(models.Model):

    class TipoInmueble(models.TextChoices):
        CASA = "CASA", "Casa"
        APARTAMENTO = "APARTAMENTO", "Apartamento"
        LOCAL_COMERCIAL = "LOCAL_COMERCIAL", "Local comercial"
        TERRENO = "TERRENO", "Terreno"
        OFICINA = "OFICINA", "Oficina"
        BODEGA = "BODEGA", "Bodega"

    class TipoOperacion(models.TextChoices):
        VENTA = "VENTA", "Venta"
        ARRIENDO = "ARRIENDO", "Arriendo"

    class EstadoComercial(models.TextChoices):
        DISPONIBLE = "DISPONIBLE", "Disponible"
        RESERVADO = "RESERVADO", "Reservado"
        VENDIDO = "VENDIDO", "Vendido"
        ALQUILADO = "ALQUILADO", "Alquilado"

    class EstadoRevision(models.TextChoices):
        PENDIENTE = "PENDIENTE", "Pendiente"
        APROBADA = "APROBADA", "Aprobada"
        RECHAZADA = "RECHAZADA", "Rechazada"
        DEVUELTA = "DEVUELTA", "Devuelta"

    java_id = models.PositiveBigIntegerField(
        unique=True,
        verbose_name="ID del inmueble en Java"
    )

    titulo = models.CharField(max_length=150)
    descripcion = models.TextField(max_length=2000, blank=True)
    precio = models.DecimalField(max_digits=14, decimal_places=2)
    direccion = models.CharField(max_length=200)
    ciudad = models.CharField(max_length=100, blank=True)

    tipo = models.CharField(
        max_length=30,
        choices=TipoInmueble.choices
    )

    operacion = models.CharField(
        max_length=20,
        choices=TipoOperacion.choices
    )

    estado_comercial = models.CharField(
        max_length=20,
        choices=EstadoComercial.choices,
        default=EstadoComercial.DISPONIBLE
    )

    area = models.FloatField(null=True, blank=True)
    habitaciones = models.PositiveIntegerField(null=True, blank=True)
    banos = models.PositiveIntegerField(null=True, blank=True)
    fecha_publicacion = models.DateTimeField(null=True, blank=True)

    propietario_java_id = models.PositiveBigIntegerField(null=True, blank=True)
    propietario_nombre = models.CharField(max_length=100, blank=True)
    propietario_apellido = models.CharField(max_length=100, blank=True)
    propietario_email = models.EmailField(blank=True)
    propietario_telefono = models.CharField(max_length=20, blank=True)

    estado_revision = models.CharField(
        max_length=20,
        choices=EstadoRevision.choices,
        default=EstadoRevision.PENDIENTE
    )

    observacion_revision = models.TextField(blank=True)

    administrador_revisor = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.SET_NULL,
        null=True,
        blank=True,
        related_name="inmuebles_revisados"
    )

    fecha_revision = models.DateTimeField(null=True, blank=True)
    fecha_recepcion = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "Inmueble"
        verbose_name_plural = "Inmuebles"
        ordering = ["-fecha_recepcion"]

    def clean(self):
        super().clean()

        if self.estado_revision in [
            self.EstadoRevision.RECHAZADA,
            self.EstadoRevision.DEVUELTA
        ] and not self.observacion_revision.strip():
            raise ValidationError({
                "observacion_revision":
                "Debes escribir una observación para rechazar o devolver el inmueble."
            })

    def __str__(self):
        return self.titulo


def obtener_inmuebles_publicados():
    return Inmueble.objects.filter(
        estado_revision=Inmueble.EstadoRevision.APROBADA
    )


class ImagenInmueble(models.Model):
    inmueble = models.ForeignKey(
        Inmueble,
        on_delete=models.CASCADE,
        related_name="imagenes"
    )

    url = models.URLField(max_length=500)
    descripcion = models.CharField(max_length=255, blank=True)
    principal = models.BooleanField(default=False)

    def __str__(self):
        return f"Imagen de {self.inmueble.titulo}"


class Plano2D(models.Model):
    inmueble = models.ForeignKey(
        Inmueble,
        on_delete=models.CASCADE,
        related_name="planos"
    )

    nombre = models.CharField(max_length=150)
    url = models.URLField(max_length=500)
    piso = models.IntegerField(null=True, blank=True)
    datos3d = models.TextField(blank=True)

    def __str__(self):
        return f"{self.nombre} - {self.inmueble.titulo}"
