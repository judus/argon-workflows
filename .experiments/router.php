<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicFile = __DIR__ . $uri;

if ($uri !== '/' && is_file($publicFile)) {
    return false;
}

switch ($uri) {
    case '/':
        require __DIR__ . '/index.html';
        break;

    case '/api/stream':
        require __DIR__ . '/server/index.php';
        break;

    case '/api/graph':
        require __DIR__ . '/server/graph.php';
        break;

    default:
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Not found: $uri";
        break;
}
