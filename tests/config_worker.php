<?php

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

echo json_encode([
    'base_url' => BASE_URL,
    'timezone' => date_default_timezone_get(),
    'storage' => dirname(rtrim(IMG_EQUIPOS, '/' . chr(92))),
    'db_host' => DB_HOST,
    'db_port' => DB_PORT,
    'db_name' => DB_NAME,
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
