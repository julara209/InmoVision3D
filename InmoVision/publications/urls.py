from django.contrib.auth.views import LogoutView
from django.urls import path

from . import views

urlpatterns = [
    # Acceso
    path("", views.inicio, name="inicio"),
    path("login/", views.InicioSesionView.as_view(), name="login"),
    # LogoutView solo acepta POST: el botón "Cerrar sesión" es un formulario.
    path("logout/", LogoutView.as_view(), name="logout"),
    path("registro/", views.registro_cliente, name="registro"),

    # Publicador
    path("panel/", views.panel_publicador, name="panel_publicador"),
    path(
        "mis-inmuebles/<int:pk>/",
        views.detalle_inmueble_propio,
        name="detalle_inmueble_propio"
    ),
    path(
        "mis-observaciones/",
        views.mis_observaciones,
        name="mis_observaciones"
    ),
    path(
        "cargar-csv/",
        views.cargar_inmuebles_csv,
        name="cargar_inmuebles_csv"
    ),

    # Catálogo (inmuebles aprobados)
    path("catalogo/", views.catalogo, name="catalogo"),
    path(
        "catalogo/<int:pk>/",
        views.detalle_inmueble_publico,
        name="detalle_inmueble_publico"
    ),
]
