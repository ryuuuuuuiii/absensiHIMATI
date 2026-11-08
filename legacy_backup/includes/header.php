<?php
/**
 * Header Template
 * Absensi HIMA TI Politeknik Hasnur
 */
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Absensi Digital HIMA TI Politeknik Hasnur - Pencatatan kehadiran rapat dan kegiatan himpunan">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= APP_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo1.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    
    <!-- Additional CSS -->
    <?php if (isset($extraCSS)) { ?>
        <?php foreach ($extraCSS as $css) { ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php } ?>
    <?php } ?>
</head>
<body>
    <div class="bg-animated"></div>
    
    <div class="app-wrapper">
        <!-- Sidebar -->
        <?php include __DIR__.'/sidebar.php'; ?>
        
        <!-- Sidebar Overlay (Mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Header -->
            <header class="app-header">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">
                        <?php if (isset($pageIcon)) { ?>
                            <i class="<?= $pageIcon ?>"></i>
                        <?php } ?>
                        <?= htmlspecialchars($pageTitle) ?>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-user" onclick="toggleUserMenu()">
                        <div class="header-user-avatar">
                            <?= $currentUser ? getInitials($currentUser['nama_lengkap']) : '?' ?>
                        </div>
                        <div class="header-user-info">
                            <span class="header-user-name"><?= $currentUser ? htmlspecialchars($currentUser['nama_lengkap']) : 'Guest' ?></span>
                            <span class="header-user-role"><?= $currentUser ? htmlspecialchars($currentUser['jabatan']) : '' ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Toast Container -->
            <div class="toast-container" id="toastContainer"></div>
            
            <!-- Flash Messages -->
            <?php renderFlash(); ?>
            
            <!-- Page Content -->
