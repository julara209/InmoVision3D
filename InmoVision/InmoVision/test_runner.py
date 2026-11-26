from django.apps import apps
from django.test.runner import DiscoverRunner


class EjecutorConTablasJava(DiscoverRunner):
    """
    Las tablas de la app `inmovision_java` son managed = False porque las crea
    Hibernate (Java). Al ejecutar las pruebas no existe esa base real, así que
    aquí se marcan como gestionadas SOLO durante la prueba para que Django cree
    unas tablas temporales de ejemplo en la base de pruebas (test_...).
    Nunca toca la base de datos real.
    """

    def setup_test_environment(self, **kwargs):
        super().setup_test_environment(**kwargs)

        self._modelos_java = list(
            apps.get_app_config("inmovision_java").get_models()
        )

        for modelo in self._modelos_java:
            modelo._meta.managed = True

    def teardown_test_environment(self, **kwargs):
        for modelo in self._modelos_java:
            modelo._meta.managed = False

        super().teardown_test_environment(**kwargs)
