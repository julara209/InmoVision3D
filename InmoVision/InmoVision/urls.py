from django.contrib import admin
from django.urls import path, include
from django.views.generic import RedirectView

urlpatterns = [
    path("", RedirectView.as_view(pattern_name="inicio", permanent=False)),
    path("admin/", admin.site.urls),
    path("publicaciones/", include("publications.urls")),
]
