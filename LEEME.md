# InmoVision 3D — Java (Spring Boot) + módulo Django

| Carpeta | Qué es |
|---|---|
| `InmoVision3D_SpringBoot/` | Aplicación Java (núcleo del sistema). Sin cambios por la integración con Django |
| `modulo_django/` | Módulo complementario en Python/Django. Mismo MySQL/MariaDB. Admin, autenticación, CSRF y mapa |

Los dos proyectos comparten la base de datos `inmovision3d`. Para instalar el módulo Django sigue
`modulo_django/README.md` (entorno virtual, `.env`, `migrate`, `createsuperuser`, `runserver`).

Orden recomendado: 1) encender MySQL, 2) arrancar Java una vez (Hibernate crea las tablas),
3) instalar y arrancar Django.
