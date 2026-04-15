<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri === '/') {
    require __DIR__ . '/index/auth.php';
    return true;
}

$file = __DIR__ . '/index' . $uri;
if (file_exists($file)) {
    return false;
}

require __DIR__ . '/index/auth.php';