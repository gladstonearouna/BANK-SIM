
<?php

$file = $_GET['file'] ?? '';

if (
    !is_string($file) ||
    !preg_match('/^[a-zA-Z0-9_-]+\.php$/', $file)
) {
    http_response_code(404);
    exit('Page introuvable');
}

$path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $file;

if (!is_file($path)) {
    http_response_code(404);
    exit('Page introuvable');
}

require $path;
