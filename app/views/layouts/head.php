<?php
// ============================================================
// GestActivos - Layout: <head> compartido
// ============================================================
$menuCssVersion = @filemtime(BASE_PATH . '/public/css/menu.css') ?: APP_VERSION;
$appCssVersion = @filemtime(BASE_PATH . '/public/css/app.css') ?: APP_VERSION;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> <?= isset($pageTitle) ? '· ' . $pageTitle : '' ?></title>

    <!-- Bootstrap 5.3: base única del proyecto -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">

    <!-- Tipografía estilo Mazer -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">

    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- jQuery se conserva para DataTables y los módulos AJAX existentes -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <!-- Bootstrap 5 bundle incluye Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Estilos del proyecto -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/menu.css?v=<?= urlencode((string)$menuCssVersion) ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/app.css?v=<?= urlencode((string)$appCssVersion) ?>">

    <script>
        // Configuración global de toastr (un solo lugar para todo el proyecto)
        toastr.options = {
            closeButton: false, debug: false, newestOnTop: false, progressBar: false,
            positionClass: 'toast-top-right', preventDuplicates: false, onclick: null,
            showDuration: '300', hideDuration: '1000', timeOut: '5000', extendedTimeOut: '1000',
            showEasing: 'swing', hideEasing: 'linear', showMethod: 'fadeIn', hideMethod: 'fadeOut'
        };
    </script>
</head>
<body>
<a class="skip-link" href="#contenido-principal">Saltar al contenido principal</a>
