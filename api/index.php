<?php

// Pastikan direktori sementara /tmp untuk view dan cache tersedia di Vercel
if (! is_dir('/tmp/views')) {
    @mkdir('/tmp/views', 0755, true);
}
if (! is_dir('/tmp/cache')) {
    @mkdir('/tmp/cache', 0755, true);
}

// Forward Vercel requests to normal public/index.php
require __DIR__.'/../public/index.php';
