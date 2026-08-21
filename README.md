# GestActivos

Sistema web para administrar inventario, empleados, proveedores, asignaciones, devoluciones, mantenimientos, actas y reportes.

Tecnologías principales: PHP 8, MySQL/MariaDB, Apache, Bootstrap 5, jQuery/DataTables y TCPDF.

El esquema de instalación es compatible con MariaDB 10.4 o posterior y MySQL 8.0 o posterior. La conexión y todas las tablas utilizan `utf8mb4`.


## Configuración para XAMPP y Docker

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


## Arquitectura actual

El flujo normal de una petición es:

`página o endpoint` → `autenticación y permiso` → `servicio/regla de negocio` → `Databasep` → `vista o respuesta AJAX`

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


