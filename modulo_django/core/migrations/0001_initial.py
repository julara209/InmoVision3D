import core.models
import django.db.models.deletion
from django.db import migrations, models


class Migration(migrations.Migration):
    """
    Los modelos de las tablas de Java son `managed = False`: aquí solo se registran en el estado
    de migraciones (no ejecutan SQL). Lo único que crea tabla es UbicacionInmueble.
    """

    initial = True

    dependencies = []

    operations = [
        migrations.CreateModel(
            name="Usuario",
            fields=[
                ("id", models.BigAutoField(primary_key=True, serialize=False)),
                ("nombre", models.CharField(max_length=100)),
                ("apellido", models.CharField(max_length=100)),
                ("email", models.CharField(max_length=150, unique=True)),
                ("password", models.CharField(max_length=255)),
                ("telefono", models.CharField(blank=True, max_length=20, null=True)),
                ("rol", models.CharField(max_length=20)),
                ("fecha_registro", models.DateTimeField()),
                ("activo", core.models.BitBooleanField()),
            ],
            options={"db_table": "usuarios", "managed": False},
        ),
        migrations.CreateModel(
            name="Inmueble",
            fields=[
                ("id", models.BigAutoField(primary_key=True, serialize=False)),
                ("titulo", models.CharField(max_length=150)),
                ("descripcion", models.CharField(blank=True, max_length=2000, null=True)),
                ("precio", models.DecimalField(decimal_places=2, max_digits=14)),
                ("direccion", models.CharField(max_length=200)),
                ("ciudad", models.CharField(blank=True, max_length=100, null=True)),
                ("tipo", models.CharField(max_length=30)),
                ("operacion", models.CharField(max_length=20)),
                ("estado", models.CharField(max_length=30)),
                ("area", models.FloatField(blank=True, null=True)),
                ("habitaciones", models.IntegerField(blank=True, null=True)),
                ("banos", models.IntegerField(blank=True, null=True)),
                ("fecha_publicacion", models.DateTimeField()),
                (
                    "propietario",
                    models.ForeignKey(
                        db_column="propietario_id",
                        on_delete=django.db.models.deletion.DO_NOTHING,
                        related_name="inmuebles",
                        to="core.usuario",
                    ),
                ),
            ],
            options={"db_table": "inmuebles", "ordering": ["-id"], "managed": False},
        ),
        migrations.CreateModel(
            name="ImagenInmueble",
            fields=[
                ("id", models.BigAutoField(primary_key=True, serialize=False)),
                ("url", models.CharField(max_length=500)),
                ("descripcion", models.CharField(blank=True, max_length=255, null=True)),
                ("principal", core.models.BitBooleanField()),
                (
                    "inmueble",
                    models.ForeignKey(
                        db_column="inmueble_id",
                        on_delete=django.db.models.deletion.DO_NOTHING,
                        related_name="imagenes",
                        to="core.inmueble",
                    ),
                ),
            ],
            options={"db_table": "imagenes_inmueble", "managed": False},
        ),
        migrations.CreateModel(
            name="Plano2D",
            fields=[
                ("id", models.BigAutoField(primary_key=True, serialize=False)),
                ("nombre", models.CharField(max_length=150)),
                ("url", models.CharField(max_length=500)),
                ("piso", models.IntegerField(blank=True, db_column="numero_piso", null=True)),
                ("datos_3d", models.TextField(blank=True, null=True)),
                (
                    "inmueble",
                    models.ForeignKey(
                        db_column="inmueble_id",
                        on_delete=django.db.models.deletion.DO_NOTHING,
                        related_name="planos",
                        to="core.inmueble",
                    ),
                ),
            ],
            options={"db_table": "planos_2d", "managed": False},
        ),
        migrations.CreateModel(
            name="Favorito",
            fields=[
                ("id", models.BigAutoField(primary_key=True, serialize=False)),
                ("fecha_agregado", models.DateTimeField()),
                (
                    "inmueble",
                    models.ForeignKey(
                        db_column="inmueble_id",
                        on_delete=django.db.models.deletion.DO_NOTHING,
                        related_name="favoritos",
                        to="core.inmueble",
                    ),
                ),
                (
                    "usuario",
                    models.ForeignKey(
                        db_column="usuario_id",
                        on_delete=django.db.models.deletion.DO_NOTHING,
                        related_name="favoritos",
                        to="core.usuario",
                    ),
                ),
            ],
            options={
                "db_table": "favoritos",
                "managed": False,
                "unique_together": {("usuario", "inmueble")},
            },
        ),
        migrations.CreateModel(
            name="Solicitud",
            fields=[
                ("id", models.BigAutoField(primary_key=True, serialize=False)),
                ("mensaje", models.CharField(max_length=1000)),
                ("estado", models.CharField(max_length=20)),
                ("fecha_solicitud", models.DateTimeField()),
                ("fecha_cita", models.DateTimeField(blank=True, null=True)),
                (
                    "inmueble",
                    models.ForeignKey(
                        db_column="inmueble_id",
                        on_delete=django.db.models.deletion.DO_NOTHING,
                        related_name="solicitudes",
                        to="core.inmueble",
                    ),
                ),
                (
                    "usuario",
                    models.ForeignKey(
                        db_column="usuario_id",
                        on_delete=django.db.models.deletion.DO_NOTHING,
                        related_name="solicitudes",
                        to="core.usuario",
                    ),
                ),
            ],
            options={"db_table": "solicitudes", "managed": False},
        ),
        migrations.CreateModel(
            name="UbicacionInmueble",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("latitud", models.FloatField(help_text="Entre -90 y 90. Ej: 4.7110")),
                ("longitud", models.FloatField(help_text="Entre -180 y 180. Ej: -74.0721")),
                (
                    "inmueble",
                    models.OneToOneField(
                        db_constraint=False,
                        on_delete=django.db.models.deletion.DO_NOTHING,
                        related_name="ubicacion",
                        to="core.inmueble",
                    ),
                ),
            ],
            options={
                "verbose_name": "ubicación en el mapa",
                "verbose_name_plural": "ubicaciones en el mapa",
            },
        ),
    ]
