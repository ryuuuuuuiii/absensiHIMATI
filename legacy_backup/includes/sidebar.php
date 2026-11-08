<?php
/**
 * Sidebar Navigation — Admin Only
 * Absensi HIMA TI Politeknik Hasnur
 */
$currentUser = getCurrentUser();
$currentFile = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Get logos
$logo1 = getSetting('logo_1', '');
$logo2 = getSetting('logo_2', '');
$logo3 = getSetting('logo_3', '');
?>

<aside class="sidebar" id="sidebar">
    <!-- Sidebar Header with Logos -->
    <div class="sidebar-header">
        <div class="sidebar-logos">
            <?php if ($logo1) { ?>
                <img src="<?= BASE_URL ?>/uploads/logos/<?= $logo1 ?>" alt="Logo 1">
            <?php } else { ?>
                <div class="login-logos"><span class="logo-placeholder"><i class="fas fa-image"></i></span></div>
            <?php } ?>
            <?php if ($logo2) { ?>
                <img src="<?= BASE_URL ?>/uploads/logos/<?= $logo2 ?>" alt="Logo 2">
            <?php } else { ?>
                <div class="login-logos"><span class="logo-placeholder"><i class="fas fa-image"></i></span></div>
            <?php } ?>
            <?php if ($logo3) { ?>
                <img src="<?= BASE_URL ?>/uploads/logos/<?= $logo3 ?>" alt="Logo 3">
            <?php } else { ?>
                <div class="login-logos"><span class="logo-placeholder"><i class="fas fa-image"></i></span></div>
            <?php } ?>
        </div>
        <div class="sidebar-brand">
            <?= APP_NAME ?>
            <small>Panel Admin</small>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Menu Utama</div>
            
            <a href="<?= BASE_URL ?>/dashboard/index.php" 
               class="nav-item <?= ($currentDir === 'dashboard' && $currentFile === 'index') ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Kelola</div>
            
            <a href="<?= BASE_URL ?>/admin/members.php" 
               class="nav-item <?= $currentFile === 'members' || $currentFile === 'add_member' || $currentFile === 'edit_member' ? 'active' : '' ?>">
                <i class="fas fa-users-cog"></i>
                <span>Kelola Anggota</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/events.php" 
               class="nav-item <?= $currentFile === 'events' || $currentFile === 'event_detail' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Kelola Event</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/meetings.php" 
               class="nav-item <?= $currentFile === 'meetings' && $currentDir === 'admin' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Kelola Rapat</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/reports.php" 
               class="nav-item <?= $currentFile === 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Laporan</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Pengaturan</div>
            
            <a href="<?= BASE_URL ?>/admin/generate_qr.php" 
               class="nav-item <?= $currentFile === 'generate_qr' ? 'active' : '' ?>">
                <i class="fas fa-qrcode"></i>
                <span>Generate QR Code</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/settings.php" 
               class="nav-item <?= $currentFile === 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cogs"></i>
                <span>Pengaturan</span>
            </a>
        </div>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/rapat" class="nav-item" target="_blank" style="color: var(--primary);">
            <i class="fas fa-external-link-alt"></i>
            <span>Link Buat Rapat</span>
        </a>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-item" style="color: var(--danger);">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
