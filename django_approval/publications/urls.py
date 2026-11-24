from django.urls import path
from . import views

urlpatterns = [
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
]
