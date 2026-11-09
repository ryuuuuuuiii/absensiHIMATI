<?php

// Sembunyikan pesan deprecation agar tidak merusak output header HTTP
error_reporting(E_ALL & ~E_DEPRECATED);

// Pastikan direktori sementara /tmp untuk view dan cache tersedia di Vercel
if (! is_dir('/tmp/views')) {
    @mkdir('/tmp/views', 0755, true);
}
if (! is_dir('/tmp/cache/data')) {
    @mkdir('/tmp/cache/data', 0755, true);
}
if (! is_dir('/tmp/cache/locks')) {
    @mkdir('/tmp/cache/locks', 0755, true);
}

// Forward Vercel requests to normal public/index.php
require __DIR__.'/../public/index.php';
