<?php
// ============================================================
// GestActivos - Layout: <head> compartido
// ============================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> <?= isset($pageTitle) ? '· ' . $pageTitle : '' ?></title>

    <!-- Bootstrap 3 (versión del proyecto base) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">

    <!-- Tipografía estilo Mazer -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- jQuery (debe ir antes de Bootstrap JS) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Estilos del proyecto -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/menu.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/app.css">

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