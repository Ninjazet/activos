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

$rutaActual = trim(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/');
$baseMenu = trim((string)(parse_url(BASE_URL, PHP_URL_PATH) ?? ''), '/');
if ($baseMenu !== '' && strpos($rutaActual, $baseMenu . '/') === 0) {
    $rutaActual = substr($rutaActual, strlen($baseMenu) + 1);
}
$esRutaActiva = static function (string $url) use ($rutaActual): bool {
    return trim($url, '/') === $rutaActual;
};
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

        <!-- ACCESOS PRINCIPALES Y CATÁLOGOS -->
        <?php if ($mae == '1'): ?>
        <div class="item menu-directo">
            <a href="<?= BASE_URL ?>/equipos.php" class="<?= $esRutaActiva('equipos.php') ? 'active' : '' ?>">
                <div class="icon"><img src="<?= BASE_URL ?>/public/icons/equipo.png" alt=""></div>
                <div class="title"><span>Inventario</span></div>
            </a>
        </div>

        <div class="item menu-directo">
            <a href="<?= BASE_URL ?>/empleados.php" class="<?= $esRutaActiva('empleados.php') ? 'active' : '' ?>">
                <div class="icon"><img src="<?= BASE_URL ?>/public/icons/empleados.png" alt=""></div>
                <div class="title"><span>Personal</span></div>
            </a>
        </div>

        <?php
        $submenuCatalogos = [
            'marcas.php'  => ['icons/marca.png',  'Marcas'],
            'modelos.php' => ['icons/modelo.png', 'Modelos'],
            'areas.php'   => ['icons/area.png',   'Áreas'],
            'cargo.php'   => ['icons/cargo.png',  'Cargos'],
        ];
        $catalogoActivo = false;
        foreach (array_keys($submenuCatalogos) as $urlCatalogo) {
            $catalogoActivo = $catalogoActivo || $esRutaActiva($urlCatalogo);
        }
        ?>
        <div class="item has-submenu <?= $catalogoActivo ? 'is-open' : '' ?>" id="catalogos">
            <a href="#catalogos" class="<?= $catalogoActivo ? 'active' : '' ?>">
                <div class="icon"><i class="fa-solid fa-gears"></i></div>
                <div class="title"><span>Configuración / Catálogos</span></div>
            </a>
            <div class="subitem">
                <?php foreach ($submenuCatalogos as $url => [$icon, $label]): ?>
                <a href="<?= BASE_URL ?>/<?= $url ?>" class="<?= $esRutaActiva($url) ? 'active' : '' ?>">
                    <div class="icon"><img src="<?= BASE_URL ?>/public/<?= $icon ?>" alt=""></div>
                    <div class="title"><span><?= $label ?></span></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="item separator"></div>
        <?php endif; ?>

        <!-- TRANSACCIONES -->
        <?php if ($tran == '1'): ?>
        <div class="item has-submenu" id="tran">
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
        <div class="item has-submenu" id="con">
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
        <div class="item has-submenu" id="repo">
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
        <div class="item has-submenu" id="seg">
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

<div id="topbar">
    <div id="topbar-title">
        <h1><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Inicio' ?></h1>
        <span>Panel de control · <?= htmlspecialchars(APP_NAME) ?></span>
    </div>
    <div id="topbar-user">
        <img src="<?= htmlspecialchars($imagen) ?>" alt="">
        <span><?= htmlspecialchars($nomApe) ?></span>
    </div>
</div>

<script>
const btn  = document.querySelector('#menu-btn');
const menu = document.querySelector('#sidemenu');

// Resalta la página actual y mantiene abierto su grupo en todo el menú.
const currentPath = window.location.pathname.replace(/\/+$/, '');
$('#menu-items a[href]').each(function () {
    const href = this.getAttribute('href');
    if (!href || href.charAt(0) === '#') {
        return;
    }
    const linkPath = new URL(this.href, window.location.origin).pathname.replace(/\/+$/, '');
    if (linkPath === currentPath) {
        $(this).addClass('active');
        $(this).closest('.item.has-submenu').addClass('is-open').children('.subitem').show();
    }
});

btn.addEventListener('click', () => {
    menu.classList.toggle('menu-expanded');
    menu.classList.toggle('menu-collapsed');
    document.body.classList.toggle('body-expanded');
});

$('#menu-items').on('click', '.item.has-submenu > a', function (event) {
    event.preventDefault();

    if (document.body.classList.contains('body-expanded')) {
        menu.classList.toggle('menu-expanded');
        menu.classList.toggle('menu-collapsed');
        document.body.classList.toggle('body-expanded');
    }
    const item = $(this).parent();
    const sub = item.children('.subitem');
    if (item.hasClass('is-open')) {
        sub.stop(true, true).slideUp(160, function () {
            item.removeClass('is-open');
        });
    } else {
        item.addClass('is-open');
        sub.stop(true, true).slideDown(160);
    }
});
</script>
