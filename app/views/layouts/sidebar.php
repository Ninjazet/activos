<?php
// ============================================================
// GestActivos - Layout: Sidebar de navegación
// Los datos del usuario ya están en $_SESSION desde el login.
// No volvemos a consultar la BD aquí (el login ya lo hizo).
// ============================================================

$nomApe = Auth::get('nombre');
$imagen = Auth::get('foto');

// Si por alguna razón falta la foto en sesión, usar el avatar por defecto
if (empty($imagen)) {
    $imagen = BASE_URL . '/public/img/empleados/avatar1.png';
}

$mae  = $_SESSION['maestros']      ?? '0';
$tran = $_SESSION['transacciones'] ?? '0';
$con  = $_SESSION['consultas']     ?? '0';
$rep  = $_SESSION['reportes']      ?? '0';
$seg  = $_SESSION['seguridad']     ?? '0';
?>

<div id="sidemenu" class="menu-expanded">
    <div id="header">
        <div id="title"><span><?= APP_NAME ?></span></div>
        <div id="menu-btn">
            <div class="btn-hamburger"></div>
            <div class="btn-hamburger"></div>
            <div class="btn-hamburger"></div>
        </div>
    </div>

    <div id="profile">
        <div id="photo">
            <img style="background-color:white;" id="mifoto" src="<?= htmlspecialchars($imagen) ?>" alt="">
        </div>
        <div id="name"><span><?= htmlspecialchars($nomApe) ?></span></div>
        <a href="<?= BASE_URL ?>/index.php"><div class="icon"><i class="fas fa-home"></i></div></a>
        <br>
        <a href="<?= BASE_URL ?>/cerrar.php" id="cerrar">[Cerrar Sesión]</a>
        <a href="<?= BASE_URL ?>/cerrar.php" title="Cerrar Sesión" id="cerrarIcon">
            <i class="fa-solid fa-power-off"></i>
        </a>
    </div>

    <div id="menu-items">

        <!-- DATOS MAESTROS -->
        <?php if ($mae == '1'): ?>
        <div class="item" id="mae">
            <a href="#mae">
                <div class="icon"><img src="<?= BASE_URL ?>/public/icons/Maestros.png" alt=""></div>
                <div class="title"><span>Datos Maestros</span></div>
            </a>
            <?php
            $submenuMaestros = [
                'empleados.php'  => ['icons/empleados.png', 'Empleados'],
                'cargo.php'      => ['icons/cargo.png',     'Cargos'],
                'equipos.php'    => ['icons/equipo.png',    'Equipos'],
                'marcas.php'     => ['icons/marca.png',     'Marcas'],
                'modelos.php'    => ['icons/modelo.png',    'Modelos'],
                'areas.php'      => ['icons/area.png',      'Áreas'],
            ];
            foreach ($submenuMaestros as $url => [$icon, $label]): ?>
            <div class="subitem">
                <a href="<?= BASE_URL ?>/<?= $url ?>">
                    <div class="icon"><img src="<?= BASE_URL ?>/public/<?= $icon ?>"></div>
                    <div class="title"><span><?= $label ?></span></div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="item separator"></div>
        <?php endif; ?>

        <!-- TRANSACCIONES -->
        <?php if ($tran == '1'): ?>
        <div class="item" id="tran">
            <a href="#tran">
                <div class="icon"><img src="<?= BASE_URL ?>/public/icons/Transacciones.png" alt=""></div>
                <div class="title"><span>Transacciones</span></div>
            </a>
            <div class="subitem">
                <a href="<?= BASE_URL ?>/asignarequipo.php">
                    <div class="icon"><img src="<?= BASE_URL ?>/public/icons/asignacion.png"></div>
                    <div class="title"><span>Asignar Equipos</span></div>
                </a>
            </div>
        </div>
        <div class="item separator"></div>
        <?php endif; ?>

        <!-- CONSULTAS -->
        <?php if ($con == '1'): ?>
        <div class="item" id="con">
            <a href="#con">
                <div class="icon"><img src="<?= BASE_URL ?>/public/icons/consulta.png" alt=""></div>
                <div class="title"><span>Consultas</span></div>
            </a>
            <?php
            $submenuConsultas = [
                'consultas/empleados.php'   => 'Empleados',
                'consultas/cargos.php'      => 'Cargos',
                'consultas/equipos.php'     => 'Equipos',
                'consultas/marcas.php'      => 'Marcas',
                'consultas/modelos.php'     => 'Modelos',
                'consultas/asignaciones.php'=> 'Asignaciones',
                'consultas/areas.php'       => 'Áreas',
            ];
            foreach ($submenuConsultas as $url => $label): ?>
            <div class="subitem">
                <a href="<?= BASE_URL ?>/<?= $url ?>">
                    <div class="icon"><i class="fas fa-search"></i></div>
                    <div class="title"><span><?= $label ?></span></div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="item separator"></div>
        <?php endif; ?>

        <!-- REPORTES -->
        <?php if ($rep == '1'): ?>
        <div class="item" id="repo">
            <a href="#repo">
                <div class="icon"><img src="<?= BASE_URL ?>/public/icons/reportes.png" alt=""></div>
                <div class="title"><span>Reportes</span></div>
            </a>
            <?php
            $submenuReportes = [
                'reportes/empleados.php'   => 'Reporte de Empleados',
                'reportes/equipos.php'     => 'Reporte de Equipos',
                'reportes/asignaciones.php'=> 'Reporte de Asignaciones',
            ];
            foreach ($submenuReportes as $url => $label): ?>
            <div class="subitem">
                <a href="<?= BASE_URL ?>/<?= $url ?>">
                    <div class="icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="title"><span><?= $label ?></span></div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="item separator"></div>
        <?php endif; ?>

        <!-- SEGURIDAD -->
        <?php if ($seg == '1'): ?>
        <div class="item" id="seg">
            <a href="#seg">
                <div class="icon"><img src="<?= BASE_URL ?>/public/icons/seguridad.png" alt=""></div>
                <div class="title"><span>Seguridad</span></div>
            </a>
            <div class="subitem">
                <a href="<?= BASE_URL ?>/usuarios.php">
                    <div class="icon"><img src="<?= BASE_URL ?>/public/icons/usuario.png"></div>
                    <div class="title"><span>Usuarios</span></div>
                </a>
                <a href="<?= BASE_URL ?>/bitacora.php">
                    <div class="icon"><img src="<?= BASE_URL ?>/public/icons/permisos.png"></div>
                    <div class="title"><span>Bitácora</span></div>
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<div id="main-container"></div>

<script>
const btn  = document.querySelector('#menu-btn');
const menu = document.querySelector('#sidemenu');

btn.addEventListener('click', () => {
    menu.classList.toggle('menu-expanded');
    menu.classList.toggle('menu-collapsed');
    document.body.classList.toggle('body-expanded');
});

$('.item').on('click', function (event) {
    // Si el clic fue en una sub-opción real (un link a una página, ej. Empleados),
    // dejamos que el navegador navegue normalmente, sin interferir.
    if ($(event.target).closest('.subitem').length) {
        return;
    }

    // Si el clic fue en el título de la categoría (ej. "Datos Maestros"),
    // evitamos que el navegador salte al ancla "#mae" y solo desplegamos el submenú.
    event.preventDefault();

    if (document.body.classList.contains('body-expanded')) {
        menu.classList.toggle('menu-expanded');
        menu.classList.toggle('menu-collapsed');
        document.body.classList.toggle('body-expanded');
    }
    const sub = $(this).children('.subitem');
    sub.css('display') === 'none' ? sub.show() : sub.hide();
});
</script>
