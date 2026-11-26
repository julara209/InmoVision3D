"""
Modelos del módulo Django.

1) Tablas de la aplicación Java  -> `managed = False`: Django solo las lee, nunca las crea,
   altera ni borra. Equivalen a la salida de `python manage.py inspectdb`, ya ajustada
   (nombres de clase en singular y CamelCase, ForeignKey reales, tablas con `db_table`).
2) Tablas propias de Django      -> modelos normales con migraciones (ej. UbicacionInmueble).
"""
from django.core.exceptions import ValidationError
from django.db import models


class BitBooleanField(models.BooleanField):
    """
    Hibernate guarda los boolean como BIT(1). El conector devuelve bytes (b'\\x00' / b'\\x01')
    y b'\\x00' sería "verdadero" en Python, así que se convierte a bool de forma explícita.
    """

    def from_db_value(self, value, expression, connection):
        if value is None:
            return None
        if isinstance(value, (bytes, bytearray)):
            return any(value)
        return bool(value)


# ───────────────────────── Tablas de Java (managed = False) ─────────────────────────

class Usuario(models.Model):
    id = models.BigAutoField(primary_key=True)
    nombre = models.CharField(max_length=100)
    apellido = models.CharField(max_length=100)
    email = models.CharField(unique=True, max_length=150)
    password = models.CharField(max_length=255)  # hash BCrypt; nunca se muestra en el admin
    telefono = models.CharField(max_length=20, blank=True, null=True)
    rol = models.CharField(max_length=20)  # ADMIN, PUBLICADOR, CLIENTE
    fecha_registro = models.DateTimeField()
    activo = BitBooleanField()

    class Meta:
        managed = False
        db_table = "usuarios"

    def __str__(self):
        return f"{self.nombre} {self.apellido}"


class Inmueble(models.Model):
    id = models.BigAutoField(primary_key=True)
    titulo = models.CharField(max_length=150)
    descripcion = models.CharField(max_length=2000, blank=True, null=True)
    precio = models.DecimalField(max_digits=14, decimal_places=2)
    direccion = models.CharField(max_length=200)
    ciudad = models.CharField(max_length=100, blank=True, null=True)
    tipo = models.CharField(max_length=30)       # CASA, APARTAMENTO, LOCAL_COMERCIAL, TERRENO, OFICINA, BODEGA
    operacion = models.CharField(max_length=20)  # VENTA, ARRIENDO
    estado = models.CharField(max_length=30)     # DISPONIBLE, RESERVADO, VENDIDO, ALQUILADO
    area = models.FloatField(blank=True, null=True)
    habitaciones = models.IntegerField(blank=True, null=True)
    banos = models.IntegerField(blank=True, null=True)
    fecha_publicacion = models.DateTimeField()
    propietario = models.ForeignKey(
        Usuario, db_column="propietario_id", related_name="inmuebles", on_delete=models.DO_NOTHING
    )

    class Meta:
        managed = False
        db_table = "inmuebles"
        ordering = ["-id"]

    def __str__(self):
        return self.titulo


class ImagenInmueble(models.Model):
    id = models.BigAutoField(primary_key=True)
    url = models.CharField(max_length=500)
    descripcion = models.CharField(max_length=255, blank=True, null=True)
    principal = BitBooleanField()
    inmueble = models.ForeignKey(
        Inmueble, db_column="inmueble_id", related_name="imagenes", on_delete=models.DO_NOTHING
    )

    class Meta:
        managed = False
        db_table = "imagenes_inmueble"

    def __str__(self):
        return self.url


class Plano2D(models.Model):
    id = models.BigAutoField(primary_key=True)
    nombre = models.CharField(max_length=150)
    url = models.CharField(max_length=500)
    piso = models.IntegerField(db_column="numero_piso", blank=True, null=True)
    datos_3d = models.TextField(blank=True, null=True)
    inmueble = models.ForeignKey(
        Inmueble, db_column="inmueble_id", related_name="planos", on_delete=models.DO_NOTHING
    )

    class Meta:
        managed = False
        db_table = "planos_2d"

    def __str__(self):
        return self.nombre


class Favorito(models.Model):
    id = models.BigAutoField(primary_key=True)
    usuario = models.ForeignKey(
        Usuario, db_column="usuario_id", related_name="favoritos", on_delete=models.DO_NOTHING
    )
    inmueble = models.ForeignKey(
        Inmueble, db_column="inmueble_id", related_name="favoritos", on_delete=models.DO_NOTHING
    )
    fecha_agregado = models.DateTimeField()

    class Meta:
        managed = False
        db_table = "favoritos"
        unique_together = (("usuario", "inmueble"),)

    def __str__(self):
        return f"{self.usuario} ♥ {self.inmueble}"


class Solicitud(models.Model):
    id = models.BigAutoField(primary_key=True)
    usuario = models.ForeignKey(
        Usuario, db_column="usuario_id", related_name="solicitudes", on_delete=models.DO_NOTHING
    )
    inmueble = models.ForeignKey(
        Inmueble, db_column="inmueble_id", related_name="solicitudes", on_delete=models.DO_NOTHING
    )
    mensaje = models.CharField(max_length=1000)
    estado = models.CharField(max_length=20)  # PENDIENTE, ACEPTADA, RECHAZADA
    fecha_solicitud = models.DateTimeField()
    fecha_cita = models.DateTimeField(blank=True, null=True)

    class Meta:
        managed = False
        db_table = "solicitudes"

    def __str__(self):
        return f"Solicitud #{self.id}"


# ───────────────────────── Tablas propias de Django (con migraciones) ─────────────────────────

class UbicacionInmueble(models.Model):
    """
    Coordenadas del inmueble para el mapa. Vive en una tabla de Django (core_ubicacioninmueble),
    así la tabla `inmuebles` de Java no se toca. Sin restricción FK en la base de datos
    (db_constraint=False): Java puede borrar inmuebles sin enterarse de esta tabla.
    """
    inmueble = models.OneToOneField(
        Inmueble, related_name="ubicacion", on_delete=models.DO_NOTHING, db_constraint=False
    )
    latitud = models.FloatField(help_text="Entre -90 y 90. Ej: 4.7110")
    longitud = models.FloatField(help_text="Entre -180 y 180. Ej: -74.0721")

    class Meta:
        verbose_name = "ubicación en el mapa"
        verbose_name_plural = "ubicaciones en el mapa"

    def clean(self):
        if self.latitud is not None and not -90 <= self.latitud <= 90:
            raise ValidationError({"latitud": "La latitud debe estar entre -90 y 90."})
        if self.longitud is not None and not -180 <= self.longitud <= 180:
            raise ValidationError({"longitud": "La longitud debe estar entre -180 y 180."})

    def __str__(self):
        return f"{self.latitud}, {self.longitud}"
