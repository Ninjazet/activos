# GestActivos

Sistema web para administrar inventario, empleados, proveedores, asignaciones, devoluciones, mantenimientos, actas y reportes.

Tecnologías principales: PHP 8, MySQL/MariaDB, Apache, Bootstrap 5, jQuery/DataTables y TCPDF.

El esquema de instalación es compatible con MariaDB 10.4 o posterior y MySQL 8.0 o posterior. La conexión y todas las tablas utilizan `utf8mb4`.

## Inicio local con XAMPP

1. Coloca el proyecto en `C:\xampp\htdocs\activos`.
2. Crea una base llamada `gestactivos` con codificación `utf8mb4`.
3. Selecciona esa base e importa `database/gestactivos.sql`.
4. Copia `.env.example` como `.env` y ajusta únicamente los valores de tu instalación.
5. Inicia Apache y MySQL desde XAMPP.
6. Abre `http://localhost/activos/` o la URL definida en `APP_BASE_URL`.

El archivo `.env` contiene datos locales y está excluido de Git. Nunca deben guardarse contraseñas reales en `.env.example`.

`database/gestactivos.sql` es el único respaldo de instalación y contiene el esquema junto con todos los datos existentes al 11 de agosto de 2026. No ejecutes migraciones adicionales después de importarlo.

## Configuración para XAMPP y Docker

La aplicación toma su configuración desde variables del sistema o desde un archivo `.env`. Las variables del sistema tienen prioridad.

| Variable | Uso | Valor local habitual |
|---|---|---|
| `APP_BASE_URL` | Ruta pública de la aplicación | `/activos` |
| `APP_TIMEZONE` | Zona horaria | `America/Tegucigalpa` |
| `APP_STORAGE_PATH` | Carpeta persistente que contiene imágenes y firmas | vacío para usar `public/img` |
| `APP_ENCRYPTION_KEY` | Llave local de 32 bytes para proteger claves de licencias | generar una distinta por instalación |
| `DB_HOST` | Servidor de base de datos | `localhost`; en Docker suele ser `db` |
| `DB_PORT` | Puerto de MySQL/MariaDB | `3306` |
| `DB_USER`, `DB_PASS`, `DB_NAME` | Credenciales y base | según la instalación |
| `DB_CHARSET` | Codificación de conexión | `utf8mb4` |

Si se configura `APP_STORAGE_PATH`, PHP intentará crear automáticamente las subcarpetas `empleados`, `equipos` y `firmas` cuando se usen. En Docker, monta un volumen persistente en esa ruta y concede escritura al usuario que ejecuta Apache/PHP. Por ejemplo, se puede usar `APP_STORAGE_PATH=/var/lib/gestactivos/media` y montar el volumen en `/var/lib/gestactivos/media`.

Las fotos de empleados y equipos se entregan mediante la ruta autenticada `media.php`. Por eso el volumen puede estar fuera de la carpeta pública del servidor y las imágenes seguirán cargando con cualquier dominio o valor válido de `APP_BASE_URL`.

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
    mantenimientos/              Operación e historial técnico de equipos
    licencias/                   Catálogo, registro y ficha de licencias
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
- `ProveedorService` y `ProveedorController`: administran el catálogo ampliado y su ficha de compras.
- `MantenimientoEstado`: centraliza tipos, estados, resultados y estilos de mantenimiento.
- `MantenimientoService`: abre, actualiza, cancela y cierra mantenimientos manteniendo sincronizado el estado del equipo.
- `SoftwareService`: administra productos, fabricantes, versiones y ediciones sin borrar su historial.
- `LicenciaService`: registra compras, vigencias, titulares y claves protegidas, y controla sus bajas lógicas.
- `LicenciaEstado`: centraliza modalidades, métricas, destinos permitidos y estados calculados de vigencia.
- `SecretoLicencia`: cifra y enmascara claves de producto mediante la llave local de cada instalación.
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

No borres historial de asignaciones ni mantenimientos. Las bajas de empleados, equipos, proveedores y catálogos son lógicas y deben respetar sus dependencias.

## Proveedores y mantenimientos

- `proveedores.php` permite buscar, crear, editar, inactivar, reactivar y abrir la ficha de un proveedor. El proveedor es opcional en equipos y mantenimientos.
- `mantenimientos.php` requiere el permiso independiente `mantenimientos`. Solo admite equipos activos y Disponibles, sin asignación ni mantenimiento abierto.
- Un mantenimiento Reparado devuelve el equipo a Disponible. Un resultado No reparable lo deja Dado de baja. Cancelar conserva el historial y restaura un estado operativo seguro.
- Una devolución con condición Con daño o No funcional cierra la asignación y abre el mantenimiento correctivo dentro de la misma transacción.
- El estado En mantenimiento no se elige manualmente desde Inventario; lo controla este flujo.

Esta estructura ya está incorporada en `database/gestactivos.sql`.

## Licencias de software

La base del módulo separa el catálogo `software` de las compras `licencias` y conserva historiales independientes de cupos, asignaciones, instalaciones y renovaciones. El acceso utiliza el permiso independiente `licencias`.

- `software.php` permite crear, editar, buscar, filtrar, inactivar y reactivar productos. Un producto con licencias activas no puede darse de baja.
- `licencias.php` registra compras, modalidad, métrica, cantidad, vigencia, proveedor, costo, contrato, titular y clave de producto opcional.
- `licencia.php` muestra la ficha comercial y el estado calculado sin exponer la clave completa.
- Desde la ficha, un usuario con permiso `licencias` puede revelar u obtener una copia de la clave. La consulta usa POST, CSRF, respuesta sin caché y deja el evento `revelar_clave` en la bitácora.
- Las licencias con cantidad finita generan cupos numerados automáticamente. La ficha permite asignarlos a empleados o equipos según la métrica y devolverlos sin borrar el historial.
- Una devolución libera el cupo cuando la licencia es reutilizable; si no lo es, el cupo queda consumido. No se puede inactivar un empleado o equipo mientras conserve licencias activas.
- Las licencias no se eliminan físicamente. Una licencia con asignaciones o instalaciones activas no puede desactivarse.
- Los proveedores inactivos y productos inactivos se conservan en registros históricos, pero no se aceptan en compras nuevas.

Las claves de producto nunca deben almacenarse sin protección. `SecretoLicencia` usa cifrado autenticado y obtiene la llave maestra exclusivamente de `APP_ENCRYPTION_KEY`. Para restaurar este respaldo conservando el acceso a las claves existentes, copia de forma privada la misma `APP_ENCRYPTION_KEY` de la instalación original. No incluyas esa llave en Git ni dentro del SQL.

En una publicación con dominio, la aplicación debe servirse mediante HTTPS para proteger también la clave mientras viaja entre el navegador y el servidor al solicitar verla o copiarla.

El esquema, los productos, las licencias y sus cupos actuales ya están incorporados en `database/gestactivos.sql`.

## Pruebas de regresión

Desde la raíz del proyecto ejecuta:

```powershell
C:\xampp\php\php.exe tests\run.php
```

La suite comprueba reglas de estados, validaciones, configuración por entorno, conexión y esquema, integridad de asignaciones y mantenimientos, restricciones SQL, archivos referenciados, permisos AJAX, renderizado de páginas, generación de PDF y sintaxis PHP global. No crea, edita ni elimina datos operativos; solamente genera sesiones temporales dentro de `tests/.tmp`.

`tests/module_flow_worker.php` cubre escrituras reales de proveedores, cierres de mantenimiento y devoluciones con daño. Tiene un bloqueo de seguridad y solo funciona cuando `DB_NAME` contiene `_feature_test_`; debe ejecutarse sobre una copia temporal desechable, nunca sobre la base operativa.

`tests/licencia_flow_worker.php` valida en una base temporal la creación y edición de software y licencias, generación y ajuste de cupos, asignación, devolución, cifrado de claves, prevención de duplicados y reglas de inactivación/reactivación. Requiere además una `APP_ENCRYPTION_KEY` de prueba.

Después de una modificación visual, completa además una revisión manual en escritorio y móvil. Para cambios de asignación, recorre siempre creación, firma de entrega y devolución.

## Documentos del proyecto

- `documentacion_tecnica.html`: explicación extensa del sistema para otro programador.
- `flujo-trabajo.html`: recorrido visual de los procesos principales.
- `database/gestactivos.sql`: único respaldo completo de instalación; incluye el esquema final y los datos actuales.
