<?php
/**
 * Login Page (Admin Only)
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/dashboard/index.php');
}

// Get logos
$logo1 = getSetting('logo_1', '');
$logo2 = getSetting('logo_2', '');
$logo3 = getSetting('logo_3', '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login Admin Sistem Absensi HIMA TI Politeknik Hasnur">
    <title>Login Admin — <?= APP_FULL_NAME ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="bg-animated"></div>
    
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <!-- 3 Logo Space -->
                <div class="login-logos">
                    <?php if ($logo1): ?>
                        <img src="<?= BASE_URL ?>/uploads/logos/<?= $logo1 ?>" alt="Logo 1">
                    <?php else: ?>
                        <span class="logo-placeholder"><i class="fas fa-university"></i></span>
                    <?php endif; ?>
                    
                    <?php if ($logo2): ?>
                        <img src="<?= BASE_URL ?>/uploads/logos/<?= $logo2 ?>" alt="Logo 2">
                    <?php else: ?>
                        <span class="logo-placeholder"><i class="fas fa-laptop-code"></i></span>
                    <?php endif; ?>
                    
                    <?php if ($logo3): ?>
                        <img src="<?= BASE_URL ?>/uploads/logos/<?= $logo3 ?>" alt="Logo 3">
                    <?php else: ?>
                        <span class="logo-placeholder"><i class="fas fa-users"></i></span>
                    <?php endif; ?>
                </div>
                
                <!-- Title -->
                <div class="login-title">
                    <h1><?= APP_NAME ?></h1>
                    <p>Panel Admin — Politeknik Hasnur</p>
                </div>
                
                <!-- Flash Message -->
                <?php renderFlash(); ?>
                
                <!-- Login Form -->
                <form action="<?= BASE_URL ?>/auth/process_login.php" method="POST" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope"></i> Email Admin
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="admin@himati.id"
                               required
                               autocomplete="email"
                               value="<?= isset($_SESSION['login_email']) ? htmlspecialchars($_SESSION['login_email']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div style="position: relative;">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Masukkan password"
                                   required
                                   autocomplete="current-password"
                                   style="padding-right: 45px;">
                            <button type="button" 
                                    onclick="togglePassword()" 
                                    style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px;"
                                    aria-label="Toggle password visibility">
                                <i class="fas fa-eye" id="togglePwdIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: var(--space-6);">
                        <i class="fas fa-sign-in-alt"></i>
                        Masuk Panel Admin
                    </button>
                </form>
                
                <!-- Link ke halaman publik -->
                <div style="text-align: center; margin-top: var(--space-6); padding-top: var(--space-4); border-top: 1px solid var(--dark-border);">
                    <a href="<?= BASE_URL ?>/rapat" style="color: var(--primary); font-size: var(--font-sm); text-decoration: none;">
                        <i class="fas fa-external-link-alt"></i> Buat Rapat (Halaman Publik)
                    </a>
                    <p style="font-size: var(--font-xs); color: var(--text-muted); margin-top: var(--space-3);">
                        <i class="fas fa-shield-alt"></i> &copy; <?= date('Y') ?> Politeknik Hasnur
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('togglePwdIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        <?php unset($_SESSION['login_email']); ?>
    </script>
</body>
</html>
