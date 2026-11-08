<?php

/**
 * Auth Check Middleware
 * Include this at the top of protected pages
 */

require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/functions.php';

if (! isLoggedIn()) {
    setFlash('warning', 'Silakan login terlebih dahulu.');
    redirect(BASE_URL.'/auth/login.php');
}
