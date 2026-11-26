# Módulo Django — InmoVision 3D

Módulo complementario en Python/Django que **comparte la base de datos MySQL/MariaDB** de la aplicación
Java (Spring Boot). Aporta panel de administración, autenticación, validación de formularios, protección
CSRF y el mapa de inmuebles. Sobre este módulo se construyen las siguientes historias de usuario.

## Reglas técnicas del módulo
| Qué | Cómo se maneja |
|---|---|
| Tablas de Java (`usuarios`, `inmuebles`, `imagenes_inmueble`, `planos_2d`, `favoritos`, `solicitudes`) | Modelos con `managed = False`. Django solo las lee: nunca las crea, altera ni borra |
| Tablas nuevas de Django (admin, sesiones, `core_ubicacioninmueble`, y las futuras) | Modelos normales + migraciones (`makemigrations` / `migrate`) |
| Credenciales | Solo en el archivo `.env` (ignorado por git). El código no contiene contraseñas ni claves |

## Requisitos
- Python 3.9 a 3.12
- La base de datos de la aplicación Java encendida (por defecto `localhost:3307`, base `inmovision3d`) y haber
  arrancado la app Java al menos una vez (Hibernate crea las tablas)
- Git

## Instalación paso a paso

### 1. Entorno virtual
```bash
cd modulo_django
python -m venv venv
venv\Scripts\activate            # Windows
# source venv/bin/activate       # Mac / Linux
```

### 2. Dependencias
```bash
pip install -r requirements.txt
```
`mysqlclient` suele instalarse sin problemas en Windows. Si falla:
- **Linux (Debian/Ubuntu):** `sudo apt install python3-dev default-libmysqlclient-dev build-essential pkg-config` y repite el `pip install`
- **macOS:** `brew install mysql-client pkg-config` y `export PKG_CONFIG_PATH="$(brew --prefix mysql-client)/lib/pkgconfig"`
- **Windows con error de compilación:** actualiza pip (`python -m pip install --upgrade pip`) y usa Python 3.12 o 3.11

### 3. Variables de entorno (.env)
```bash
copy .env.example .env           # Windows   (Mac/Linux: cp .env.example .env)
```
Abre `.env` y completa **tus** valores: `DB_USER`, `DB_PASSWORD` (en XAMPP, usuario `root` y contraseña vacía),
`DB_HOST`, `DB_PORT` (el mismo de `application.yaml`) y `DJANGO_SECRET_KEY`. Para generar la clave:
```bash
python -c "from django.core.management.utils import get_random_secret_key; print(get_random_secret_key())"
```
> `.env` está en `.gitignore`: **nunca lo subas**. Cada integrante tiene el suyo.

### 4. Probar la conexión
```bash
python manage.py check --database default
```
Debe responder `System check identified no issues`. Si falta alguna variable, el error dice cuál.

### 5. (Opcional) Comparar con inspectdb
Los modelos de `core/models.py` equivalen a la salida de `inspectdb` ya ajustada (nombres, relaciones y
`managed = False`). Para verificar que siguen igual a tu base de datos real:
```bash
python manage.py inspectdb > inspectdb_salida.py
```
Compara columnas y tipos de `usuarios`, `inmuebles`, `imagenes_inmueble`, `planos_2d`, `favoritos` y `solicitudes`
(el archivo generado está en `.gitignore`). Si Java cambia una tabla, actualiza el modelo y la migración.

### 6. Crear las tablas de Django
```bash
python manage.py migrate
```
Crea solo tablas nuevas de Django (`auth_*`, `django_*`, `core_ubicacioninmueble`). **No toca las tablas de Java.**
Recomendado: respaldar la base de datos antes de la primera vez.

Si quieres confirmar que los modelos y las migraciones están sincronizados:
```bash
python manage.py makemigrations --check --dry-run
```

### 7. Crear tu usuario administrador
```bash
python manage.py createsuperuser
```
Es un usuario **de Django** (distinto a los usuarios de la aplicación Java).

### 8. Arrancar
```bash
python manage.py runserver 8000
```
- Panel de administración: http://localhost:8000/admin/
- Mapa de inmuebles: http://localhost:8000/mapa/

## Panel de administración
Lista los inmuebles reales de MySQL, además de usuarios, imágenes, planos, favoritos y solicitudes. Todo es de
**solo lectura** (las tablas son de Java) salvo la **ubicación en el mapa**: abre un inmueble y llena latitud y
longitud en el bloque inferior. Solo los inmuebles `DISPONIBLE` con ubicación aparecen en `/mapa/`.
El hash de la contraseña de los usuarios nunca se muestra.

## La aplicación Java no se modifica
- Django no escribe en tablas de Java (`managed = False`, y el admin de esas tablas es de solo lectura).
- Las tablas nuevas de Django tienen otros nombres; Hibernate (`ddl-auto: update`) ignora tablas que no conoce.
- Sin restricciones FK hacia `inmuebles`: Java puede borrar un inmueble sin errores.

## Añadir funcionalidad en próximas historias
- Tabla nueva que necesita Django (ej. revisión de publicaciones): modelo normal en `core/models.py` →
  `python manage.py makemigrations` → `python manage.py migrate`.
- Tabla de Java que Django necesita leer: modelo con `managed = False` y `db_table` (como los existentes).

## Credenciales y seguridad
- `.env` y `inspectdb_salida.py` están en `.gitignore`. `.env.example` solo trae valores de ejemplo.
- Antes de cada push puedes revisar que no se cuele nada: `git grep -n -i "password\|secret"` (solo debe aparecer código).
- Si alguna vez subes una contraseña por error, cámbiala en la base de datos: borrar el commit no basta.

## Ficha del inmueble en el mapa
Al hacer clic en un marcador se abre una ficha con: tipo, modalidad (venta/arriendo), precio, área, habitaciones,
baños, dirección y foto principal, más dos enlaces del **mismo inmueble**:
- **Ver plano 2D:** abre el archivo del plano (`/uploads/...` de la app Java).
- **Ver recorrido 3D:** abre `/planos/visor3d/{id}` de la app Java.

Se usa el **primer plano** del inmueble, igual que hace Java. Si falta algo no hay enlaces rotos: el botón aparece
deshabilitado ("Plano 2D no disponible" / "Recorrido 3D no disponible") y un aviso amarillo explica qué falta.

> El visor 3D de Java exige haber iniciado sesión (`anyRequest().authenticated()` en `SecurityConfig`): si el cliente
> no tiene sesión, Java lo manda al login antes de mostrar el visor.

## Mapa dentro de la aplicación Java (opcional)
El mapa funciona por sí solo en `http://localhost:8000/mapa/`. Para enlazarlo desde el menú de la app Java
agrega `<a href="http://localhost:8000/mapa/">Mapa</a>` en la plantilla del menú.
`/mapa/?embed=1` oculta la barra superior (para un `<iframe>`) y `/mapa/?id=12` abre el inmueble 12.

## Problemas frecuentes
| Mensaje | Solución |
|---|---|
| `Falta la variable de entorno ...` | Crea `.env` desde `.env.example` y complétalo |
| `Access denied for user` | Revisa `DB_USER` / `DB_PASSWORD` en `.env` |
| `Can't connect to MySQL server` | Enciende MySQL/MariaDB y verifica `DB_HOST` y `DB_PORT` |
| `Unknown database` | `DB_NAME` debe ser la base que usa Java (`inmovision3d`) |
| `Table 'inmovision3d.inmuebles' doesn't exist` | Arranca primero la app Java para que Hibernate cree las tablas |
| `Error loading MySQLdb module` | Reinstala `mysqlclient` (ver paso 2) |
