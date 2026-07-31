# GestActivos

Sistema web para administrar inventario, empleados, asignaciones, devoluciones, actas y reportes.

Tecnologías principales: PHP 8, MySQL/MariaDB, Apache, Bootstrap 5, jQuery/DataTables y TCPDF.

## Inicio local con XAMPP

1. Coloca el proyecto en `C:\xampp\htdocs\activos`.
2. Importa `database/gestactivos.sql` en MySQL/MariaDB.
3. Copia `.env.example` como `.env` y ajusta únicamente los valores de tu instalación.
4. Inicia Apache y MySQL desde XAMPP.
5. Abre `http://localhost/activos/` o la URL definida en `APP_BASE_URL`.

El archivo `.env` contiene datos locales y está excluido de Git. Nunca deben guardarse contraseñas reales en `.env.example`.

## Configuración para XAMPP y Docker

La aplicación toma su configuración desde variables del sistema o desde un archivo `.env`. Las variables del sistema tienen prioridad.

| Variable | Uso | Valor local habitual |
|---|---|---|
| `APP_BASE_URL` | Ruta pública de la aplicación | `/activos` |
| `APP_TIMEZONE` | Zona horaria | `America/Tegucigalpa` |
| `APP_STORAGE_PATH` | Carpeta persistente que contiene imágenes y firmas | vacío para usar `public/img` |
| `DB_HOST` | Servidor de base de datos | `localhost`; en Docker suele ser `db` |
| `DB_PORT` | Puerto de MySQL/MariaDB | `3306` |
| `DB_USER`, `DB_PASS`, `DB_NAME` | Credenciales y base | según la instalación |
| `DB_CHARSET` | Codificación de conexión | `utf8mb4` |

Si se configura `APP_STORAGE_PATH`, PHP intentará crear automáticamente las subcarpetas `empleados`, `equipos` y `firmas` cuando se usen. En Docker, el volumen debe ser persistente y permitir escritura al usuario que ejecuta Apache/PHP.

## Arquitectura actual

El flujo normal de una petición es:

`página o endpoint` → `autenticación y permiso` → `servicio/regla de negocio` → `Database` → `vista o respuesta AJAX`

Las responsabilidades están distribuidas así:

```text
bootstrap.php                    Carga configuración y clases comunes
config/
  env.php                        Variables de entorno y archivo .env
  app.php                        URL, zona horaria y almacenamiento
  database.php                   Parámetros de la base
app/
  controllers/                   Entrada HTTP, sesión, permisos, base y cargas
  domain/                        Reglas y valores propios del negocio
  services/                      Operaciones y transacciones reutilizables
  support/                       Utilidades comunes sin HTML
  views/
    layouts/                     Estructura general de las páginas
    maestros/                    Vistas reutilizadas por catálogos
    transacciones/asignaciones/  Modales, checklist y scripts de asignación
  ajax/                          Endpoints que responden tablas o fragmentos HTML
consultas/                       Páginas de consulta
reportes/                        Vistas, actas y generación de PDF
public/                          CSS, JavaScript, iconos e imágenes públicas
tests/                           Suite automatizada de regresión
database/                        Respaldo y migraciones SQL
```

Toda entrada PHP debe comenzar cargando `bootstrap.php`. No deben incluirse manualmente clases individuales.

## Fuentes únicas de reglas comunes

- `EquipoEstado`: identificadores, nombres, opciones y estilos de los estados del equipo. No vuelvas a crear arreglos de estados dentro de una página.
- `Imagen`: valida la referencia almacenada y devuelve la imagen real o el avatar predeterminado.
- `Validacion`: normaliza fechas, correos, costos, textos, identificadores y números de serie.
- `EquipoFormulario`: transforma los formularios de creación y edición de equipos en datos normalizados.
- `AsignacionService`: protege el ciclo de asignación dentro de transacciones de base de datos.
- `CatalogoService` y `CatalogoController`: reúnen el CRUD de áreas, cargos, marcas y modelos.

## Cómo modificar un módulo existente

1. Ubica primero su página principal y su endpoint en `app/ajax`.
2. Si el cambio es una regla de negocio, colócalo en `app/domain` o `app/services`, no dentro del HTML.
3. Si es una validación reutilizable, agrégala a `Validacion`.
4. Si cambia una etiqueta o estado de equipo, modifica únicamente `EquipoEstado`.
5. Conserva `Auth::requerirPermiso(...)` en cada archivo invocable directamente.
6. En todo formulario que escriba datos, conserva el campo y la verificación CSRF.
7. Usa consultas preparadas y `Database::transaccion()` cuando una acción actualice más de una tabla.
8. Ejecuta la suite de regresión antes de probar visualmente.

## Cómo agregar funcionalidad nueva

Para un catálogo simple, agrega su definición permitida en `CatalogoService`, crea las dos rutas pequeñas que delegan en `CatalogoController` y agrega el enlace con su permiso en el sidebar. Las tablas y modales comunes no deben copiarse.

Para una operación de negocio:

1. Crea un servicio en `app/services` con validación y transacción.
2. Deja en la página principal solo autenticación, CSRF, llamada al servicio, bitácora y redirección.
3. Coloca los fragmentos visuales en `app/views/<módulo>`.
4. Usa el endpoint AJAX únicamente para filtros, consultas y renderizado.
5. Agrega al menos una prueba de la nueva regla en `tests/run.php`.

No borres historial de asignaciones. Las bajas de empleados, equipos y catálogos son lógicas y deben respetar sus dependencias.

## Pruebas de regresión

Desde la raíz del proyecto ejecuta:

```powershell
C:\xampp\php\php.exe tests\run.php
```

La suite comprueba reglas de estados, validaciones, configuración por entorno, conexión y esquema, integridad de asignaciones, restricciones SQL, archivos referenciados, permisos AJAX, renderizado de páginas, generación de PDF y sintaxis PHP global. No crea, edita ni elimina datos operativos; solamente genera sesiones temporales dentro de `tests/.tmp`.

Después de una modificación visual, completa además una revisión manual en escritorio y móvil. Para cambios de asignación, recorre siempre creación, firma de entrega y devolución.

## Documentos del proyecto

- `documentacion_tecnica.html`: explicación extensa del sistema para otro programador.
- `flujo-trabajo.html`: recorrido visual de los procesos principales.
- `database/gestactivos.sql`: respaldo definitivo del esquema y los datos preparados para el repositorio.
