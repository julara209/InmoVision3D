"""
Modelos de SOLO LECTURA de las tablas que crea y mantiene la aplicación Java
(Spring Boot + Hibernate).

Se generaron con `python manage.py inspectdb` y se revisaron a mano:

* managed = False  -> Django NUNCA crea, altera ni borra estas tablas.
* Nombres de clase en singular y con sufijo "Java" para no confundirlos con
  los modelos propios de Django (publications.Inmueble, etc.).
* Los enums de Java (columnas enum/varchar) tienen `choices` legibles.
* Las columnas `bit(1)` de MariaDB (activo, principal) usan CampoBit: el
  driver las entrega como bytes (b'\\x00' / b'\\x01') y ambos serían
  "verdaderos" en Python si se usara un BooleanField normal.

Si Java cambia una entidad (nueva columna, otro nombre), se actualiza aquí a
mano; revisa el resultado con `python manage.py inspectdb <tabla>`.
"""

from django.db import models


class CampoBit(models.BooleanField):
    """Boolean guardado como bit(1) por Hibernate en MariaDB."""

    def from_db_value(self, value, expression, connection):
        if value is None:
            return None

        if isinstance(value, (bytes, bytearray)):
            return any(value)  # b'\x01' -> True, b'\x00' -> False

        return bool(value)


class ModeloJava(models.Model):
    """Base de todos los modelos de tablas gestionadas por Java."""

    class Meta:
        abstract = True
        managed = False

    def save(self, *args, **kwargs):
        raise NotImplementedError(
            "Esta tabla la gestiona la aplicación Java: Django solo la lee."
        )

    def delete(self, *args, **kwargs):
        raise NotImplementedError(
            "Esta tabla la gestiona la aplicación Java: Django solo la lee."
        )


class RolUsuario(models.TextChoices):
    ADMIN = "ADMIN", "Administrador"
    PUBLICADOR = "PUBLICADOR", "Publicador"
    CLIENTE = "CLIENTE", "Cliente"


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


class EstadoInmueble(models.TextChoices):
    DISPONIBLE = "DISPONIBLE", "Disponible"
    RESERVADO = "RESERVADO", "Reservado"
    VENDIDO = "VENDIDO", "Vendido"
    ALQUILADO = "ALQUILADO", "Alquilado"


class EstadoPublicacion(models.TextChoices):
    PENDIENTE = "PENDIENTE", "Pendiente"
    APROBADA = "APROBADA", "Aprobada"
    RECHAZADA = "RECHAZADA", "Rechazada"
    DEVUELTA = "DEVUELTA", "Devuelta"


class EstadoSolicitud(models.TextChoices):
    PENDIENTE = "PENDIENTE", "Pendiente"
    ACEPTADA = "ACEPTADA", "Aceptada"
    RECHAZADA = "RECHAZADA", "Rechazada"


class UsuarioJava(ModeloJava):
    """Tabla `usuarios` (entidad Java Usuario)."""

    id = models.BigAutoField(primary_key=True)
    nombre = models.CharField(max_length=100)
    apellido = models.CharField(max_length=100)
    email = models.CharField(max_length=150, unique=True)
    password = models.CharField(max_length=255)  # hash BCrypt; no se muestra
    telefono = models.CharField(max_length=20, blank=True, null=True)
    rol = models.CharField(max_length=20, choices=RolUsuario.choices)
    fecha_registro = models.DateTimeField()
    activo = CampoBit()

    class Meta(ModeloJava.Meta):
        db_table = "usuarios"
        verbose_name = "Usuario (Java)"
        verbose_name_plural = "Usuarios (Java)"
        ordering = ["id"]

    def __str__(self):
        return f"{self.nombre} {self.apellido} <{self.email}>"


class InmuebleJava(ModeloJava):
    """Tabla `inmuebles` (entidad Java Inmueble)."""

    id = models.BigAutoField(primary_key=True)
    titulo = models.CharField(max_length=150)
    descripcion = models.CharField(max_length=2000, blank=True, null=True)
    precio = models.DecimalField(max_digits=14, decimal_places=2)
    direccion = models.CharField(max_length=200)
    ciudad = models.CharField(max_length=100, blank=True, null=True)
    tipo = models.CharField(max_length=30, choices=TipoInmueble.choices)
    operacion = models.CharField(max_length=20, choices=TipoOperacion.choices)
    estado = models.CharField(max_length=30, choices=EstadoInmueble.choices)
    estado_publicacion = models.CharField(
        max_length=20, choices=EstadoPublicacion.choices
    )
    observacion_revision = models.CharField(
        max_length=500, blank=True, null=True
    )
    area = models.FloatField(blank=True, null=True)
    habitaciones = models.IntegerField(blank=True, null=True)
    banos = models.IntegerField(blank=True, null=True)
    fecha_publicacion = models.DateTimeField()
    propietario = models.ForeignKey(
        UsuarioJava,
        models.DO_NOTHING,
        db_column="propietario_id",
        related_name="inmuebles",
    )

    class Meta(ModeloJava.Meta):
        db_table = "inmuebles"
        verbose_name = "Inmueble (Java)"
        verbose_name_plural = "Inmuebles (Java)"
        ordering = ["-fecha_publicacion"]

    def __str__(self):
        return self.titulo


class ImagenInmuebleJava(ModeloJava):
    """Tabla `imagenes_inmueble` (entidad Java ImagenInmueble)."""

    id = models.BigAutoField(primary_key=True)
    inmueble = models.ForeignKey(
        InmuebleJava,
        models.DO_NOTHING,
        db_column="inmueble_id",
        related_name="imagenes",
    )
    url = models.CharField(max_length=500)
    descripcion = models.CharField(max_length=255, blank=True, null=True)
    principal = CampoBit()

    class Meta(ModeloJava.Meta):
        db_table = "imagenes_inmueble"
        verbose_name = "Imagen de inmueble (Java)"
        verbose_name_plural = "Imágenes de inmueble (Java)"
        ordering = ["id"]

    def __str__(self):
        return self.url


class Plano2DJava(ModeloJava):
    """Tabla `planos_2d` (entidad Java Plano2D)."""

    id = models.BigAutoField(primary_key=True)
    inmueble = models.ForeignKey(
        InmuebleJava,
        models.DO_NOTHING,
        db_column="inmueble_id",
        related_name="planos",
    )
    nombre = models.CharField(max_length=150)
    url = models.CharField(max_length=500)
    numero_piso = models.IntegerField(blank=True, null=True)
    datos_3d = models.TextField(blank=True, null=True)

    class Meta(ModeloJava.Meta):
        db_table = "planos_2d"
        verbose_name = "Plano 2D (Java)"
        verbose_name_plural = "Planos 2D (Java)"
        ordering = ["id"]

    def __str__(self):
        return self.nombre


class FavoritoJava(ModeloJava):
    """Tabla `favoritos` (entidad Java Favorito)."""

    id = models.BigAutoField(primary_key=True)
    usuario = models.ForeignKey(
        UsuarioJava,
        models.DO_NOTHING,
        db_column="usuario_id",
        related_name="favoritos",
    )
    inmueble = models.ForeignKey(
        InmuebleJava,
        models.DO_NOTHING,
        db_column="inmueble_id",
        related_name="favoritos",
    )
    fecha_agregado = models.DateTimeField()

    class Meta(ModeloJava.Meta):
        db_table = "favoritos"
        verbose_name = "Favorito (Java)"
        verbose_name_plural = "Favoritos (Java)"
        unique_together = (("usuario", "inmueble"),)
        ordering = ["-fecha_agregado"]

    def __str__(self):
        return f"{self.usuario_id} -> {self.inmueble_id}"


class SolicitudJava(ModeloJava):
    """Tabla `solicitudes` (entidad Java Solicitud)."""

    id = models.BigAutoField(primary_key=True)
    usuario = models.ForeignKey(
        UsuarioJava,
        models.DO_NOTHING,
        db_column="usuario_id",
        related_name="solicitudes",
    )
    inmueble = models.ForeignKey(
        InmuebleJava,
        models.DO_NOTHING,
        db_column="inmueble_id",
        related_name="solicitudes",
    )
    mensaje = models.CharField(max_length=1000)
    estado = models.CharField(max_length=20, choices=EstadoSolicitud.choices)
    fecha_solicitud = models.DateTimeField()
    fecha_cita = models.DateTimeField(blank=True, null=True)

    class Meta(ModeloJava.Meta):
        db_table = "solicitudes"
        verbose_name = "Solicitud (Java)"
        verbose_name_plural = "Solicitudes (Java)"
        ordering = ["-fecha_solicitud"]

    def __str__(self):
        return f"Solicitud {self.pk} ({self.estado})"
