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

<aside id="sidemenu" class="menu-expanded" aria-label="Navegación principal">
    <div id="header">
        <div id="title"><span><?= APP_NAME ?></span></div>
        <button type="button" id="menu-btn" aria-label="Contraer menú" aria-controls="sidemenu" aria-expanded="true">
            <i id="menu-toggle-icon" class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
    </div>

    <div id="profile">
        <div id="photo">
            <img id="mifoto" src="<?= htmlspecialchars($imagen) ?>" alt="Foto de <?= htmlspecialchars($nomApe) ?>">
        </div>
        <div id="name"><span><?= htmlspecialchars($nomApe) ?></span></div>
        <a href="<?= BASE_URL ?>/index.php" aria-label="Ir al inicio" title="Ir al inicio"><div class="icon"><i class="fas fa-home" aria-hidden="true"></i></div></a>
        <br>
        <a href="<?= BASE_URL ?>/cerrar.php" id="cerrar">[Cerrar Sesión]</a>
        <a href="<?= BASE_URL ?>/cerrar.php" title="Cerrar sesión" aria-label="Cerrar sesión" id="cerrarIcon">
            <i class="fa-solid fa-power-off" aria-hidden="true"></i>
        </a>
    </div>

    <nav id="menu-items" aria-label="Secciones del sistema">

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
                    <div class="icon"><img src="<?= BASE_URL ?>/public/icons/asignacion.png" alt=""></div>
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
                    <div class="icon"><img src="<?= BASE_URL ?>/public/icons/usuario.png" alt=""></div>
                    <div class="title"><span>Usuarios</span></div>
                </a>
                <a href="<?= BASE_URL ?>/bitacora.php">
                    <div class="icon"><img src="<?= BASE_URL ?>/public/icons/permisos.png" alt=""></div>
                    <div class="title"><span>Bitácora</span></div>
                </a>
            </div>
        </div>
        <?php endif; ?>

    </nav>
</aside>

<button type="button" id="menu-overlay" aria-label="Cerrar menú"></button>

<div id="topbar">
    <div class="topbar-start">
        <button type="button" id="mobile-menu-btn" aria-label="Abrir menú" aria-controls="sidemenu" aria-expanded="false">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div id="topbar-title">
            <h1><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Inicio' ?></h1>
            <span>Panel de control · <?= htmlspecialchars(APP_NAME) ?></span>
        </div>
    </div>
    <div id="topbar-user">
        <img src="<?= htmlspecialchars($imagen) ?>" alt="Foto de <?= htmlspecialchars($nomApe) ?>">
        <span><?= htmlspecialchars($nomApe) ?></span>
    </div>
</div>

<main id="contenido-principal" class="app-content" tabindex="-1">

<script>
const btn = document.querySelector('#menu-btn');
const mobileBtn = document.querySelector('#mobile-menu-btn');
const menu = document.querySelector('#sidemenu');
const menuOverlay = document.querySelector('#menu-overlay');
const menuToggleIcon = document.querySelector('#menu-toggle-icon');
const mobileMenuQuery = window.matchMedia('(max-width: 991px)');

function setMenuFocusable(enabled) {
    menu.querySelectorAll('a, button, input, select, textarea, [tabindex]').forEach(function (element) {
        if (!enabled) {
            if (!element.hasAttribute('data-menu-tabindex')) {
                element.setAttribute('data-menu-tabindex', element.getAttribute('tabindex') || '');
            }
            element.setAttribute('tabindex', '-1');
            return;
        }
        const previousTabindex = element.getAttribute('data-menu-tabindex');
        if (previousTabindex === null) { return; }
        if (previousTabindex === '') {
            element.removeAttribute('tabindex');
        } else {
            element.setAttribute('tabindex', previousTabindex);
        }
        element.removeAttribute('data-menu-tabindex');
    });
}

function setMobileMenu(open) {
    menu.classList.toggle('mobile-open', open);
    document.body.classList.toggle('sidebar-open', open);
    mobileBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    menuToggleIcon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
    menu.setAttribute('aria-hidden', open ? 'false' : 'true');
    setMenuFocusable(open);
}

function setDesktopMenu(collapsed) {
    menu.classList.toggle('menu-expanded', !collapsed);
    menu.classList.toggle('menu-collapsed', collapsed);
    document.body.classList.toggle('body-expanded', collapsed);
    btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    btn.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
    menuToggleIcon.className = collapsed ? 'fa-solid fa-angles-right' : 'fa-solid fa-bars';
}

function syncMenuMode() {
    if (mobileMenuQuery.matches) {
        document.body.classList.remove('body-expanded');
        menu.classList.remove('menu-collapsed');
        menu.classList.add('menu-expanded');
        setMobileMenu(false);
    } else {
        menu.setAttribute('aria-hidden', 'false');
        setMenuFocusable(true);
        setDesktopMenu(menu.classList.contains('menu-collapsed'));
    }
}

// Resalta la página actual y mantiene abierto su grupo en todo el menú.
const currentPath = window.location.pathname.replace(/\/+$/, '');
$('#menu-items .item.has-submenu > a').attr('aria-expanded', 'false');
$('#menu-items a[href]').each(function () {
    const href = this.getAttribute('href');
    if (!href || href.charAt(0) === '#') {
        return;
    }
    const linkPath = new URL(this.href, window.location.origin).pathname.replace(/\/+$/, '');
    if (linkPath === currentPath) {
        $(this).addClass('active');
        this.setAttribute('aria-current', 'page');
        $(this).closest('.item.has-submenu').addClass('is-open').children('.subitem').show();
        $(this).closest('.item.has-submenu').children('a').attr('aria-expanded', 'true');
    }
});

btn.addEventListener('click', () => {
    if (mobileMenuQuery.matches) {
        setMobileMenu(false);
        mobileBtn.focus();
        return;
    }
    setDesktopMenu(!menu.classList.contains('menu-collapsed'));
});

mobileBtn.addEventListener('click', () => {
    setMobileMenu(true);
    btn.focus();
});
menuOverlay.addEventListener('click', () => {
    setMobileMenu(false);
    mobileBtn.focus();
});

$('#menu-items').on('click', '.item.has-submenu > a', function (event) {
    event.preventDefault();

    if (!mobileMenuQuery.matches && menu.classList.contains('menu-collapsed')) {
        setDesktopMenu(false);
    }
    const item = $(this).parent();
    const sub = item.children('.subitem');
    if (item.hasClass('is-open')) {
        $(this).attr('aria-expanded', 'false');
        sub.stop(true, true).slideUp(160, function () {
            item.removeClass('is-open');
        });
    } else {
        $(this).attr('aria-expanded', 'true');
        item.addClass('is-open');
        sub.stop(true, true).slideDown(160);
    }
});

$('#menu-items').on('click', 'a[href]:not([href^="#"])', function () {
    if (mobileMenuQuery.matches) {
        setMobileMenu(false);
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
        setMobileMenu(false);
        mobileBtn.focus();
        return;
    }
    if (event.key === 'Tab' && document.body.classList.contains('sidebar-open')) {
        const focusables = Array.from(menu.querySelectorAll('a:not([tabindex="-1"]), button:not([disabled]):not([tabindex="-1"])'));
        if (!focusables.length) { return; }
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
});

if (typeof mobileMenuQuery.addEventListener === 'function') {
    mobileMenuQuery.addEventListener('change', syncMenuMode);
} else {
    mobileMenuQuery.addListener(syncMenuMode);
}
syncMenuMode();
</script>
