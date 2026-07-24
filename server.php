<?php

// Cerrar conexión HTTP para prevenir bloqueos de Keep-Alive en PHP CLI Server en Windows
header('Connection: close');

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
