<?php

declare(strict_types=1);

// Vercel functions have a read-only project filesystem. Laravel's generated
// runtime files are redirected to /tmp by vercel.json; ensure the view target
// exists before the framework boots.
if (! is_dir('/tmp/views')) {
    mkdir('/tmp/views', 0777, true);
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__).'/public';

require dirname(__DIR__).'/public/index.php';
