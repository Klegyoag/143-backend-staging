<?php

declare(strict_types=1);

// Vercel functions have a read-only project filesystem. Laravel's generated
// runtime files are redirected to /tmp by vercel.json; ensure the view target
// exists before the framework boots.
if (! is_dir('/tmp/views')) {
    mkdir('/tmp/views', 0777, true);
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__).'/public';

// Vercel's rewrite target is the function file, so carry the public request
// path in a query parameter and restore it before Laravel captures the request.
// Other query parameters are preserved and remain available to the app.
if (isset($_GET['vercel_path'])) {
    $requestPath = '/'.ltrim((string) $_GET['vercel_path'], '/');
    unset($_GET['vercel_path']);

    $query = http_build_query($_GET);
    $_SERVER['REQUEST_URI'] = $requestPath.($query !== '' ? '?'.$query : '');
    $_SERVER['PATH_INFO'] = $requestPath;
}

require dirname(__DIR__).'/public/index.php';
