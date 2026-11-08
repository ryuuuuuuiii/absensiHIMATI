<?php

/**
 * Root index — redirect ke halaman publik
 */
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/includes/functions.php';

// Root now redirects to public meeting page
redirect(BASE_URL.'/rapat');
