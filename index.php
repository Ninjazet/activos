<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = 'Inicio';
require BASE_PATH . '/app/views/layouts/encabezado.php';
Auth::guardarPagina(__FILE__);

$accesos = [
    ['icono' => 'icons/Maestros.png',      'titulo' => 'Datos Maestros', 'desc' => 'Empleados, cargos, equipos, marcas, modelos y áreas.',  'url' => 'empleados.php',           'permiso' => $mae  ?? '0'],
    ['icono' => 'icons/Transacciones.png', 'titulo' => 'Transacciones',  'desc' => 'Asignación de equipos a empleados.',                     'url' => 'asignarequipo.php',       'permiso' => $tran ?? '0'],
    ['icono' => 'icons/consulta.png',      'titulo' => 'Consultas',      'desc' => 'Búsqueda general de información del sistema.',           'url' => 'consultas/empleados.php', 'permiso' => $con  ?? '0'],
    ['icono' => 'icons/reportes.png',      'titulo' => 'Reportes',       'desc' => 'Reportes descargables de empleados y equipos.',          'url' => 'reportes/empleados.php',  'permiso' => $rep  ?? '0'],
];
?>

<div class="wrapper">
    <div class="dash-hero">
        <img src="<?= BASE_URL ?>/public/icons/logo.png" alt="Logo" class="dash-hero-logo">
        <div class="dash-hero-text">
            <h1><?= htmlspecialchars(APP_NAME) ?></h1>
            <p class="dash-hero-subtitle">Control de Activos Empresariales</p>
            <hr>
            <p class="dash-hero-welcome">Bienvenido, <strong><?= htmlspecialchars(Auth::get('nombre')) ?></strong>.</p>
        </div>
    </div>

    <div class="dash-grid">
        <?php foreach ($accesos as $a): if ($a['permiso'] != '1') continue; ?>
        <a href="<?= BASE_URL ?>/<?= $a['url'] ?>" class="dash-card">
            <div class="dash-card-icon"><img src="<?= BASE_URL ?>/public/<?= $a['icono'] ?>" alt=""></div>
            <div class="dash-card-title"><?= htmlspecialchars($a['titulo']) ?></div>
            <div class="dash-card-desc"><?= htmlspecialchars($a['desc']) ?></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>