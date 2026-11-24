<?php

$publicPath = __DIR__.'/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

$filePath = $publicPath.$uri;

// For video files, use Symfony BinaryFileResponse for robust HTTP 206 Byte-Range streaming
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (in_array($extension, ['mp4', 'webm', 'mov', 'ogg', 'm4v', 'mkv']) && file_exists($filePath)) {
    require_once __DIR__.'/vendor/autoload.php';
    $request = \Illuminate\Http\Request::capture();
    $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($filePath);
    $response->headers->set('Cache-Control', 'public, max-age=604800');
    $response->prepare($request);
    $response->send();
    exit;
}

// Emulate Apache's "mod_rewrite" functionality from the built-in PHP web server
if ($uri !== '/' && file_exists($filePath)) {
    return false;
}

require_once $publicPath.'/index.php';
