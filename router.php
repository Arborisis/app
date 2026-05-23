<?php
// Router for PHP built-in server ( mimics Apache mod_rewrite )
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve file directly if it exists
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// Otherwise route to index.php
require __DIR__ . '/public/index.php';
