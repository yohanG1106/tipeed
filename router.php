<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . '/index' . $uri;
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}
require __DIR__ . '/index/auth.php';