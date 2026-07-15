<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = 'Inicio';
require BASE_PATH . '/app/views/layouts/encabezado.php';
Auth::guardarPagina(__FILE__);
?>

<style>
    .wrapper { width: 90%; max-width: 1000px; margin: 0 auto; }
</style>

<div class="wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <img src="<?= BASE_URL ?>/public/icons/logo.png" alt="Logo"
                     style="width:300px;height:300px;">
            </div>
            <div class="col-md-6" style="margin-top:80px;">
                <h1 class="display-3" style="color:#222;"><?= APP_NAME ?></h1>
                <p class="lead" style="color:#444;">Control de Activos Empresariales</p>
                <hr style="border-color:#bbb; margin:20px 0;">
                <p style="color:#666;">Bienvenido al sistema.</p>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
