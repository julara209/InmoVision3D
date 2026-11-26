# InmoVision 3D

Plataforma inmobiliaria construida con **Spring Boot + Thymeleaf** que permite publicar,
explorar y gestionar inmuebles, con visualización de planos 2D/3D, solicitudes de
contacto entre clientes y publicadores, favoritos, y un panel de administración con
reportes exportables (PDF, Excel, Word).

## Descripción

InmoVision 3D es un sistema web para la gestión integral del ciclo de vida de una
publicación inmobiliaria: desde que un publicador registra un inmueble con sus
imágenes, plano 2D y modelo 3D, hasta que un cliente lo descubre, lo guarda como
favorito y envía una solicitud de contacto o visita. El sistema centraliza tres
roles de usuario (Cliente, Publicador y Administrador) sobre una única base de
datos relacional, y expone tanto vistas Thymeleaf para la navegación web como una
API REST interna que soporta las interacciones dinámicas de la interfaz (favoritos,
solicitudes, editor de planos, visor 3D). Adicionalmente, ofrece a los administradores
un módulo de reportes con filtros multicriterio, exportables en PDF, Excel o Word,
para el seguimiento del inventario, los precios y la actividad de los publicadores.

## Objetivos

### Objetivo general

Desarrollar una plataforma web que facilite la publicación, búsqueda y gestión de
inmuebles, incorporando visualización enriquecida (imágenes, planos 2D y modelos 3D)
y un flujo de contacto estructurado entre clientes y publicadores, con herramientas
de administración y generación de reportes para la toma de decisiones.

### Objetivos específicos

- Permitir a los publicadores registrar, editar y eliminar inmuebles junto con sus
  imágenes, planos 2D y modelos 3D asociados.
- Ofrecer a los clientes un catálogo de inmuebles filtrable y buscable, con detalle
  completo de cada propiedad y visualización de sus planos y modelos 3D.
- Implementar un sistema de favoritos que permita a los clientes guardar y consultar
  los inmuebles de su interés.
- Habilitar un flujo de solicitudes de contacto/visita entre clientes y publicadores,
  con seguimiento de estado y programación de citas.
- Proveer un panel de administración para la gestión de usuarios, roles, inmuebles y
  solicitudes de todo el sistema.
- Generar reportes de inventario, tipo de inmueble, precios y actividad por
  publicador, exportables en PDF, Excel y Word.
- Garantizar el acceso diferenciado a las funcionalidades del sistema según el rol
  del usuario autenticado (Cliente, Publicador, Administrador).

## Tabla de contenido

- [Descripción](#descripción)
- [Objetivos](#objetivos)
- [Stack tecnológico](#stack-tecnológico)
- [Requisitos previos](#requisitos-previos)
- [Puesta en marcha](#puesta-en-marcha)
- [Roles de usuario](#roles-de-usuario)
- [Historias de usuario](#historias-de-usuario)
- [Requisitos funcionales](#requisitos-funcionales)
- [Requisitos no funcionales](#requisitos-no-funcionales)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Rutas principales](#rutas-principales)
- [Módulo de reportes](#módulo-de-reportes)
- [Documentación técnica](#documentación-técnica)
  - [Casos de uso](#casos-de-uso)
  - [Arquitectura de componentes](#arquitectura-de-componentes)
  - [Arquitectura de despliegue](#arquitectura-de-despliegue)
  - [Arquitectura de paquetes](#arquitectura-de-paquetes)
  - [Diagrama de clases](#diagrama-de-clases)
  - [Modelo entidad-relación](#modelo-entidad-relación)

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | Java 21, Spring Boot 4.1.1 (Web MVC, Data JPA, Security, Validation) |
| Vistas | Thymeleaf + thymeleaf-extras-springsecurity6 |
| Base de datos | MariaDB 10.6+ (driver `mariadb-java-client`) |
| Reportes | Apache POI (Excel), OpenPDF (PDF), Apache POI (Word) |
| Utilidades | Lombok |
| Build | Maven |

## Requisitos previos

- **JDK 21** o superior
- **Maven** (o usar el wrapper `mvnw` / `mvnw.cmd` incluido en el proyecto)
- **MariaDB** (o MySQL compatible) corriendo en `localhost:3306`

## Puesta en marcha

### 1. Crear la base de datos

El proyecto usa `ddl-auto: update`, así que solo necesitas crear el esquema vacío;
Hibernate genera y actualiza las tablas automáticamente al arrancar.

```sql
CREATE DATABASE inmovision3d;
```

Con Docker, como alternativa rápida:

```bash
docker run --name inmovision-db \
  -e MARIADB_ROOT_PASSWORD= \
  -e MARIADB_DATABASE=inmovision3d \
  -p 3306:3306 -d mariadb:10.6
```

### 2. Configurar credenciales (si aplica)

Por defecto, `src/main/resources/application.yaml` apunta a:

```yaml
spring:
  datasource:
    url: jdbc:mariadb://localhost:3306/inmovision3d
    username: root
    password: ""
```

Si tu instalación de MariaDB usa otro usuario o contraseña, actualiza esos valores.

### 3. Ejecutar la aplicación

```bash
mvn spring-boot:run
```

La aplicación queda disponible en **http://localhost:8080**.

### 4. Crear el primer usuario ADMIN

Por seguridad, el registro público (`/auth/registro`) solo permite crear cuentas con
rol `CLIENTE` o `PUBLICADOR`. El primer usuario `ADMIN` se asigna manualmente en la
base de datos:

```sql
UPDATE usuarios SET rol = 'ADMIN' WHERE email = 'tu_correo@ejemplo.com';
```

Desde ahí, ese usuario ya puede promover a otros desde el panel de administración
(`/admin/dashboard`, pestaña Usuarios).

## Roles de usuario

| Rol | Puede |
|---|---|
| `CLIENTE` | Explorar inmuebles, marcar favoritos, enviar solicitudes de contacto |
| `PUBLICADOR` | Todo lo anterior + publicar/editar sus propios inmuebles, subir planos 2D/3D, gestionar solicitudes recibidas |
| `ADMIN` | Todo lo anterior + panel de administración: gestión de usuarios y roles, gestión de todos los inmuebles y solicitudes, generación de reportes |

## Historias de usuario

### Cliente

| ID | Historia de usuario |
|---|---|
| HU01 | Como cliente, quiero registrarme e iniciar sesión, para acceder a las funciones personalizadas de la plataforma. |
| HU02 | Como cliente, quiero editar los datos de mi perfil, para mantener actualizada mi información de contacto. |
| HU03 | Como cliente, quiero buscar y filtrar inmuebles por tipo, ciudad, operación y precio, para encontrar propiedades que se ajusten a lo que busco. |
| HU04 | Como cliente, quiero ver el detalle de un inmueble con sus imágenes, plano 2D y modelo 3D, para conocer la propiedad antes de contactar al publicador. |
| HU05 | Como cliente, quiero marcar inmuebles como favoritos y consultarlos después, para no perder de vista las propiedades de mi interés. |
| HU06 | Como cliente, quiero enviar una solicitud de contacto o visita sobre un inmueble, para coordinar con el publicador. |
| HU07 | Como cliente, quiero consultar el estado de mis solicitudes enviadas, para saber si fueron aceptadas y cuándo es la cita. |

### Publicador

| ID | Historia de usuario |
|---|---|
| HU08 | Como publicador, quiero registrar un nuevo inmueble con sus datos, imágenes, plano 2D y modelo 3D, para darlo a conocer a los clientes. |
| HU09 | Como publicador, quiero editar o eliminar mis inmuebles publicados, para mantener actualizada mi oferta. |
| HU10 | Como publicador, quiero crear y editar el plano 2D de un inmueble, para que los clientes visualicen su distribución. |
| HU11 | Como publicador, quiero visualizar el modelo 3D de mis inmuebles, para verificar que se muestra correctamente a los clientes. |
| HU12 | Como publicador, quiero consultar las solicitudes recibidas sobre mis inmuebles, para gestionarlas oportunamente. |
| HU13 | Como publicador, quiero cambiar el estado de una solicitud y programar una cita, para coordinar la visita con el cliente. |

### Administrador

| ID | Historia de usuario |
|---|---|
| HU14 | Como administrador, quiero gestionar (agregar, editar, eliminar) los usuarios del sistema, para mantener el control de acceso. |
| HU15 | Como administrador, quiero asignar y cambiar el rol de un usuario, para promoverlo o restringirlo según corresponda. |
| HU16 | Como administrador, quiero supervisar todas las publicaciones del sistema, para validar su contenido y eliminarlas si incumplen las políticas. |
| HU17 | Como administrador, quiero ver todas las solicitudes registradas en el sistema, para monitorear la actividad entre clientes y publicadores. |
| HU18 | Como administrador, quiero generar reportes de inventario, tipo, precios y publicador con filtros multicriterio, para analizar el estado del negocio. |
| HU19 | Como administrador, quiero exportar cualquier reporte en PDF, Excel o Word, para compartirlo o archivarlo según se necesite. |

## Requisitos funcionales

| ID | Requisito |
|---|---|
| RF01 | El sistema debe permitir el registro público de usuarios con rol `CLIENTE` o `PUBLICADOR`. |
| RF02 | El sistema debe permitir el inicio y cierre de sesión mediante autenticación con correo y contraseña. |
| RF03 | El sistema debe permitir a todo usuario autenticado editar los datos de su perfil. |
| RF04 | El sistema debe permitir a un `PUBLICADOR` registrar, editar y eliminar sus propios inmuebles. |
| RF05 | El sistema debe permitir subir, asociar y eliminar imágenes de un inmueble, marcando una como principal. |
| RF06 | El sistema debe permitir crear, editar y visualizar el plano 2D asociado a un inmueble. |
| RF07 | El sistema debe permitir cargar y visualizar el modelo 3D asociado a un inmueble. |
| RF08 | El sistema debe permitir a cualquier usuario, autenticado o no, explorar el catálogo público de inmuebles. |
| RF09 | El sistema debe permitir filtrar el catálogo de inmuebles por estado, tipo de operación, tipo de inmueble, publicador y texto libre. |
| RF10 | El sistema debe permitir a un `CLIENTE` marcar y desmarcar inmuebles como favoritos, y consultar su lista de favoritos. |
| RF11 | El sistema debe permitir a un `CLIENTE` enviar una solicitud de contacto/visita sobre un inmueble. |
| RF12 | El sistema debe permitir al `PUBLICADOR` consultar las solicitudes recibidas, cambiar su estado y asignar fecha y hora de cita. |
| RF13 | El sistema debe permitir a los usuarios involucrados consultar el estado y detalle de sus solicitudes (enviadas o recibidas). |
| RF14 | El sistema debe permitir a un `ADMIN` gestionar (crear, editar, eliminar, consultar) los usuarios del sistema y sus roles. |
| RF15 | El sistema debe permitir a un `ADMIN` supervisar y eliminar cualquier inmueble publicado en el sistema. |
| RF16 | El sistema debe permitir a un `ADMIN` supervisar todas las solicitudes registradas en el sistema. |
| RF17 | El sistema debe permitir a un `ADMIN` generar reportes de inventario, por tipo, de precios y por publicador, aplicando filtros multicriterio combinables. |
| RF18 | El sistema debe permitir exportar cualquier reporte generado en formato PDF, Excel o Word. |
| RF19 | El sistema debe restringir el acceso a cada funcionalidad según el rol del usuario autenticado (`CLIENTE`, `PUBLICADOR`, `ADMIN`). |

## Requisitos no funcionales

| ID | Requisito |
|---|---|
| RNF01 | **Seguridad**: el sistema debe autenticar y autorizar el acceso a las rutas mediante Spring Security, protegiendo contraseñas y restringiendo funciones por rol. |
| RNF02 | **Usabilidad**: la interfaz debe ser navegable de forma intuitiva por usuarios sin conocimientos técnicos, con retroalimentación clara ante errores de validación. |
| RNF03 | **Rendimiento**: las consultas al catálogo de inmuebles y a los reportes deben responder en tiempos aceptables aun con filtros multicriterio combinados. |
| RNF04 | **Compatibilidad**: la aplicación debe funcionar correctamente en los navegadores web modernos más usados (Chrome, Firefox, Edge). |
| RNF05 | **Portabilidad**: el sistema debe poder desplegarse con JDK 21 y MariaDB/MySQL en distintos entornos (local, contenedores Docker, servidores). |
| RNF06 | **Mantenibilidad**: el código debe organizarse por capas (controller, service, repository, model) para facilitar su mantenimiento y extensión. |
| RNF07 | **Escalabilidad**: la arquitectura en tres capas (navegador, servidor web, aplicación y base de datos) debe permitir escalar cada capa de forma independiente. |
| RNF08 | **Disponibilidad**: el sistema debe mantenerse operativo durante el horario de uso esperado, minimizando caídas del servicio. |
| RNF09 | **Confiabilidad de datos**: la persistencia de inmuebles, solicitudes, favoritos y usuarios no debe perder integridad ante fallos, apoyándose en las restricciones del modelo relacional. |
| RNF10 | **Interoperabilidad**: la comunicación entre el frontend y la aplicación debe realizarse mediante una API REST bajo `/api/**`, consumible por otros clientes si se requiere. |

## Estructura del proyecto

```
src/main/java/com/InmoVision3D/
├── controller/     Controladores MVC (vistas) y REST (@RestController bajo /api)
├── service/        Lógica de negocio (interfaces) + service/impl (implementaciones)
├── service/Reportes/  Generación de reportes (PDF, Excel, Word) y filtros (Specification)
├── repository/     Repositorios Spring Data JPA
├── model/           Entidades JPA (Usuario, Inmueble, Solicitud, Favorito, Plano2D, ImagenInmueble)
├── model/enums/     RolUsuario, EstadoInmueble, EstadoSolicitud, TipoInmueble, TipoOperacion
├── dto/             Objetos de transferencia (filtros de reportes, estadísticas)
├── security/        Configuración de Spring Security, UserDetailsService
├── exception/       Excepciones de negocio y manejador global
└── config/          Configuración adicional (recursos estáticos, uploads)

src/main/resources/
├── templates/       Vistas Thymeleaf (home, auth, inmuebles, usuario, Admin, planos)
├── static/          CSS, JS e imágenes
└── application.yaml Configuración de datasource, JPA y subida de archivos
```

## Rutas principales

| Ruta | Descripción | Acceso |
|---|---|---|
| `/` | Página de inicio | Público |
| `/inmuebles` | Catálogo de inmuebles | Público |
| `/inmuebles/{id}` | Detalle de un inmueble | Público |
| `/auth/login`, `/auth/registro` | Login y registro | Público |
| `/inmuebles/publicar` | Publicar inmueble | PUBLICADOR, ADMIN |
| `/usuario/perfil` | Perfil del usuario | Autenticado |
| `/usuario/favoritos` | Favoritos guardados | CLIENTE, ADMIN |
| `/usuario/mis-inmuebles` | Inmuebles propios | PUBLICADOR, ADMIN |
| `/solicitudes` | Solicitudes enviadas/recibidas | CLIENTE, PUBLICADOR, ADMIN |
| `/planos/editor/{inmuebleId}` | Editor de plano 2D | PUBLICADOR, ADMIN |
| `/planos/visor3d/{inmuebleId}` | Visor 3D del plano | Según el inmueble |
| `/admin/dashboard` | Panel de administración | ADMIN |
| `/admin/reportes` | Centro de reportes | ADMIN |
| `/api/**` | API REST (JSON) usada por las vistas | Ver `SecurityConfig` |

## Módulo de reportes

Desde `/admin/reportes`, el panel de administración permite generar 4 tipos de
reporte, cada uno exportable en **PDF, Excel o Word**:

- **Inventario**: listado plano de inmuebles según los filtros aplicados.
- **Por tipo**: agrupado por tipo de inmueble (casa, apartamento, local, etc.).
- **Precios**: análisis de precios por tipo de inmueble.
- **Por publicador**: resumen de inmuebles agrupados por publicador.

Los filtros son multicriterio y combinables: estado, tipo de operación (venta/arriendo),
tipo de inmueble, publicador y texto de búsqueda libre (título o ubicación).

## Documentación técnica

### Descripción general

**InmoVision** es un sistema web para la publicación, búsqueda y gestión de inmuebles, que permite a los usuarios visualizar propiedades mediante imágenes, planos 2D y modelos 3D. El sistema define tres roles principales de usuario:

- **Cliente**: busca, filtra y visualiza inmuebles, gestiona favoritos y envía solicitudes de contacto/visita.
- **Publicador**: gestiona sus inmuebles, sus planos 2D, sus modelos 3D y atiende las solicitudes que recibe.
- **Administrador**: gestiona usuarios, supervisa publicaciones y solicitudes, y genera reportes.

### Casos de uso

#### Actor: Cliente

![Diagrama de casos de uso - Cliente](https://drive.google.com/uc?export=view&id=1JQ-_6m1guDs2UeusqOvN1JZ50oxDVBb8)

| Caso de uso | Descripción |
|---|---|
| CU001 Iniciar Sesión | Autenticación del cliente en el sistema. |
| CU002 Gestionar perfil | Edición de los datos personales del cliente. |
| CU003 Visualizar Inmuebles | Buscar y filtrar inmuebles, y ver el detalle de cada uno, el cual **incluye** ver imágenes, ver plano 2D y ver modelo 3D. |
| CU004 Gestionar Favoritos | Agregar, eliminar y consultar inmuebles marcados como favoritos. |
| CU005 Gestionar solicitudes | Enviar una solicitud de contacto/visita y consultar su estado. |

#### Actor: Publicador

![Diagrama de casos de uso - Publicador](https://drive.google.com/uc?export=view&id=1NiOt4LYw9CSOxDhzpILqbxs2n75RVKCB)

| Caso de uso | Descripción |
|---|---|
| CU001 Iniciar Sesión | Autenticación del publicador en el sistema. |
| CU002 Gestionar perfil | Edición de los datos personales del publicador. |
| CU003 Gestionar Inmuebles | Registrar, editar, eliminar y consultar sus inmuebles publicados. |
| CU004 Gestionar Planos 2D | Crear, editar, eliminar y visualizar los planos 2D asociados a un inmueble. |
| CU005 Visualizar modelo 3D | Visualizar el modelo 3D asociado a un inmueble. |
| CU006 Gestionar solicitudes recibidas | Consultar las solicitudes recibidas, cambiar su estado y programar una cita. |

#### Actor: Administrador

![Diagrama de casos de uso - Administrador](https://drive.google.com/uc?export=view&id=1A2tgpthTU5liP6rJeCy8XNEyZotY0bTD)

| Caso de uso | Descripción |
|---|---|
| CU001 Iniciar Sesión | Autenticación del administrador en el sistema. |
| CU002 Gestionar usuarios | Agregar, editar, eliminar y consultar usuarios del sistema. |
| CU003 Supervisar publicaciones | Ver inmuebles publicados, eliminar publicaciones y validar su contenido. |
| CU004 Supervisar solicitudes | Ver las solicitudes registradas en el sistema. |
| CU005 Generar reportes | Aplicar filtros, generar reportes de inmuebles y descargarlos. |

### Arquitectura de componentes

![Diagrama de componentes](https://drive.google.com/uc?export=view&id=1MGxG8NstTXxwqG79tKPREJ6MGtfonLyG)

El sistema se organiza en los siguientes componentes de negocio, todos con dependencia hacia la **Base de datos**:

- **Usuarios**: administra la autenticación y los datos de los usuarios; es el componente del que dependen Inmuebles, Solicitudes y Favoritos.
- **Inmuebles**: gestiona la información de las propiedades y de él dependen los componentes multimedia:
  - **Imágenes**
  - **Plano 2D**
  - **Modelo 3D**
- **Solicitudes**: gestiona las solicitudes de contacto/visita sobre un inmueble.
- **Favoritos**: gestiona los inmuebles marcados como favoritos por un usuario.
- **Base de datos**: componente central de persistencia, consumido por todos los demás componentes.

### Arquitectura de despliegue

![Diagrama de despliegue](https://drive.google.com/uc?export=view&id=1nd3TJpYRo0jvDoROHGygjmZkmVocXsy0)

El sistema sigue una arquitectura en tres capas:

1. **Navegador** — Cliente que consume el sistema vía **HTTPS**.
2. **Servidor Web**
   - **Frontend** (HTML, CSS, JS): interfaz de usuario.
   - Se comunica con la capa de aplicación mediante **API REST**.
3. **Aplicación** — Expone la lógica de negocio de los módulos: Usuarios, Inmuebles, Solicitudes, Favoritos, Imágenes, Modelos 3D y Planos 2D. Se comunica con la base de datos mediante **SQL**.
4. **Servidor de Base de Datos** — Motor **MySQL** que almacena toda la información del sistema.

```
Navegador  --HTTPS-->  Servidor Web (Frontend)  --API REST-->  Aplicación  --SQL-->  Servidor de Base de Datos (MySQL)
```

### Arquitectura de paquetes

![Diagrama de paquetes](https://drive.google.com/uc?export=view&id=19nk7fUs7Vfoj27SDS7PF-ZVUqdKk_Z-s)

El código de la aplicación se organiza en cuatro paquetes principales:

- **Autenticación**: `Usuario`, `InicioSesion`, `PerfilUsuario`. Es el paquete base del que dependen `Gestión De Inmuebles` e `Interacción`.
- **Gestión De Inmuebles**: `Inmuebles`. Tiene dependencia bidireccional con `Interacción` y recibe dependencia del paquete `Multimedia`.
- **Interacción**: `Solicitudes`, `Favoritos`.
- **Multimedia**: `Imágenes`, `Modelo 3D`, `Plano 2D`. Depende de `Gestión De Inmuebles`.

### Diagrama de clases

![Diagrama de clases](https://drive.google.com/uc?export=view&id=1B6hMYBhKjXgsgNTSgdh1rQCcHEUEgZQU)

**Clases principales:**

- **Usuario**: `IdUsuario`, `nombre`, `apellido`, `correo`, `password`, `telefono`, `rol` `fecha_registro()`  — `registrar()`, `iniciarSesion(): Boolean`, `editarPerfil()`, `cerrarSesion()`.
- **Inmueble**: - `IdInmueble`, `area`, `banos`, `ciudad`, `direccion`, `descripcion`, `estado`, `fecha_publicacion`, `habitaciones`, `operacion`, `precio`, `tipo`, `titulo`. — `crearInmueble()`, `editaInmueble()`, `eliminarInmueble()`, `consultarInmueble(): String`.
- **Solicitudes**: `IdSolicitud`, `mensaje`, `fecha`, `estado`, `fecha_cita`, `hora_cita` — `crearSolicitud()`, `cambiarEstado()`, `asignarCita()`.
- **Favorito**: `IdFavorito`,`fecha_agregado` — `guardarFavorito()`, `eliminarFavorito()`.
- **Imagen**: `IdImagen`, `url`, `es_principal` — `subirImagen()`, `eliminarImagen()`.
- **Plano 2D**: `IdPlano`, `Archivo` — `visualizarPlano(): String`, `ampliarPlano(): Void`.
- **Modelo 3D**: `IdModelo`, `archivo` — `visualizarModelo(): String`, `recorrerModelo()`, `rotarVista()`.

**Relaciones principales:**

 Relación  Cardinalidad

Usuario *publica* Inmueble  1 a 1..* 
Usuario *realiza* Solicitudes  1 a 1..* 
Usuario *guarda* Favorito  1 a 1..* 
Favorito *contiene* Inmueble  1 a 1..* 
Solicitudes *solicita* Inmueble  1 a 1 
Inmueble *posee* Imagen  1 a 1..* 
Inmueble *incluye* Plano 2D  1 a 1 
Inmueble *posee* Modelo 3D  1 a 1 

### Modelo entidad-relación

![Modelo entidad-relación](https://drive.google.com/uc?export=view&id=17z6anOpK6Po3IcgK0QJ_llz8W02sltUG)

**Entidades y atributos principales:**

- **Usuarios**: `IdUsuario` (PK), `Nombre`, `Apellido`, `Correo`, `Contraseña`, `Telefono`.
- **Inmuebles**: `IdInmuebles` (PK), `Titulo`, `Descripcion`, `Estado`.
- **Solicitudes**: `IdSolicitudes` (PK), `Fecha`, `Estado`, `Fecha_Cita`, `Hora_Cita`.
- **Favorito**: `IdFavorito` (PK).
- **Imagenes**: `IdImagen` (PK), `es_principal`, `Url_Imagen`.
- **Planos 2D**: `IdPlano` (PK), `Archivo`.
- **Modelos 3D**: `Modelos 3D` (PK), `Archivo 3D`.

**Relaciones:**

| Relación | Entidades | Cardinalidad |
|---|---|---|
| Publica | Usuarios – Inmuebles | 1:N – 1:1 |
| Realiza | Usuarios – Solicitudes | 1:N – 1:1 |
| Guarda | Usuarios – Favorito | 1:N – 1:1 |
| Marca | Favorito – Inmuebles | 1:1 – 1:N |
| Solicita | Inmuebles – Solicitudes | 1:1 – 1:N |
| Tiene | Inmuebles – Imagenes | 1:N – 1:1 |
| Tiene | Inmuebles – Planos 2D | 1:1 – 1:1 |
| Representa | Modelos 3D – Inmuebles | 1:1 – 1:1 |

Los filtros son multicriterio y combinables: estado, tipo de operación (venta/arriendo),
tipo de inmueble, publicador y texto de búsqueda libre (título o ubicación).
