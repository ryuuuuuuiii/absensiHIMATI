<?php

// Pastikan direktori sementara /tmp untuk cache view dan storage tersedia di Vercel
if (! is_dir('/tmp/views')) {
    @mkdir('/tmp/views', 0755, true);
}
if (! is_dir('/tmp/cache')) {
    @mkdir('/tmp/cache', 0755, true);
}

// Teruskan permintaan Vercel ke public/index.php bawaan Laravel
require __DIR__.'/../public/index.php';
