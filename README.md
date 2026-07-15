# GestActivos v2.0

Sistema web de control de activos empresariales.
Stack: PHP 8 · MySQL/MariaDB · Apache (XAMPP)

---

## Estructura del proyecto

```
gestactivos/
│
├── bootstrap.php              ← Incluir al inicio de CADA archivo PHP
│
├── config/
│   ├── app.php                ← Nombre de app, zona horaria, rutas de imágenes
│   └── database.php           ← Host, usuario, contraseña, nombre de BD
│
├── app/
│   ├── controllers/
│   │   ├── Database.php       ← Clase PDO singleton con prepared statements
│   │   └── Auth.php           ← Manejo de sesión, permisos y redirecciones
│   │
│   ├── views/
│   │   └── layouts/
│   │       ├── head.php       ← <head> con todos los CSS/JS
│   │       ├── sidebar.php    ← Menú lateral dinámico por permisos
│   │       ├── encabezado.php ← Junta head + sidebar (incluir en páginas internas)
│   │       └── footer.php     ← Cierre </body></html>
│   │
│   └── ajax/                  ← Handlers AJAX (tablas + modales, retornan HTML)
│       ├── maestros/          ← empleados, cargo, areas, marcas, modelos, equipos, usuarios
│       ├── transacciones/     ← asignarequipo
│       ├── consultas/         ← tablas de solo lectura
│       └── reportes/          ← tablas + datos para los PDF
│
├── public/
│   ├── css/
│   │   ├── app.css            ← Estilos globales del proyecto
│   │   ├── menu.css           ← Estilos del sidebar
│   │   └── login.css          ← Estilos de la pantalla de login
│   ├── js/
│   │   └── ajax-loader.js     ← Función ajaxLoad() reutilizable en todos los módulos
│   ├── icons/                 ← Iconos del menú lateral
│   └── img/
│       ├── empleados/         ← Fotos de empleados subidas por el admin
│       └── equipos/           ← Fotos de equipos subidas por el admin
│
├── empleados.php, cargo.php, areas.php,        ← Páginas raíz de Datos Maestros
│   marcas.php, modelos.php, equipos.php
├── usuarios.php                                ← Página raíz de Seguridad
├── asignarequipo.php                           ← Página raíz de Transacciones
│
├── consultas/                  ← Páginas del módulo Consultas (solo lectura)
├── reportes/                   ← Páginas del módulo Reportes
│   ├── empleados.php, equipos.php,                Vista en pantalla
│   │   asignaciones.php
│   └── descargar_*.php                            Genera el PDF (TCPDF, abre en pestaña nueva)
│
├── database/
│   └── gestactivos.sql        ← Script SQL para crear e importar la BD
│
└── lib/
    └── tcpdf/                 ← Librería de generación de PDFs
```

Cada módulo sigue siempre el mismo patrón de 3 piezas:
1. **Página raíz** (`cargo.php`) → procesa el formulario (crear/editar/eliminar) y carga el listado por AJAX.
2. **Handler AJAX** (`app/ajax/maestros/cargo.php`) → genera la tabla + los modales de Bootstrap.
3. **Enlace en el menú** (`app/views/layouts/sidebar.php`) → controla quién lo ve según sus permisos.

---



Si Apache corre en otro puerto (ej. 8080 si el 80 está ocupado), la URL sería
`http://localhost:8080/gestactivos/`.

## Acceso inicial

```
URL:      http://localhost/gestactivos/
Usuario:  emartinez
Password: 12345
```

⚠️ Las contraseñas se guardan sin cifrar en la tabla `usuarios`. Está bien para
un proyecto de práctica, pero no se debería usar así en producción.

---

## Para agregar un módulo nuevo

1. Crear la página en raíz: `nuevo_modulo.php` (copia de `cargo.php`, cambiar tabla/campos)
2. Crear el AJAX handler: `app/ajax/maestros/nuevo_modulo.php` (copia de `app/ajax/maestros/cargo.php`)
3. Agregar el link en `app/views/layouts/sidebar.php`, dentro de la sección de Datos Maestros
4. Listo — no hay más archivos que tocar
