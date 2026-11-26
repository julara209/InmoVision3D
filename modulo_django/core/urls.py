from django.urls import path

from . import views

urlpatterns = [
    path("mapa/", views.mapa, name="mapa"),
    path("api/inmuebles/", views.api_inmuebles, name="api_inmuebles"),
]
