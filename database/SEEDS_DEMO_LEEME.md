# Datos de demostración

Los seeds no usan IDs fijos y pueden volver a ejecutarse sin duplicar empleados, equipos o asignaciones.

## Orden de ejecución

1. `seed_demo_base.sql`: catálogos, 30 empleados y 30 equipos; no crea asignaciones.
2. `seed_demo_asignaciones_muestra.sql`: opcional; crea 6 abiertas sin firma y 6 cerradas históricas.
3. `seed_demo_asignaciones_pendientes.sql`: desactivado por defecto; prepara 6 asignaciones adicionales y solo las crea si `@APLICAR_PENDIENTES` cambia a `1`.

## Distribución del inventario base

- 22 disponibles.
- 4 en mantenimiento.
- 2 perdidos o robados.
- 2 dados de baja.

Después de aplicar la muestra, 6 de los disponibles pasan a asignados. Quedan suficientes empleados y equipos libres para demostrar altas, asignaciones, firmas y devoluciones desde la interfaz.

Los usuarios y permisos no se cargan: se dejan empleados sin cuenta para demostrar la creación de usuarios desde Seguridad.