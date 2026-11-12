<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login Portal Pengurus HIMA TI Politeknik Hasnur">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#F8FAFC">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#090D16">
    <title>Login Pengurus — {{ config('himati.app_full_name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ \App\Models\Setting::getLogo2Url() ?? asset('assets/img/logo2.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wdth,wght@75..125,500..700&family=Noto+Sans:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ file_exists(public_path('assets/css/style.css')) ? filemtime(public_path('assets/css/style.css')) : time() }}">
    
    <script>
        (function() {
            var theme = 'auto';
            try {
                theme = localStorage.getItem('himati_theme') || 'auto';
            } catch(e) {}

            var isDark = false;
            if (theme === 'dark') {
                isDark = true;
            } else if (theme === 'light') {
                isDark = false;
            } else {
                isDark = Boolean(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            }

            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');

            window.toggleAppTheme = window.toggleAppTheme || function() {
                var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem('himati_theme', cur); } catch(e) {}
                document.documentElement.setAttribute('data-theme', cur);
            };
        })();
    </script>
</head>
<body>
    <div class="bg-animated"></div>

    <div style="position: fixed; top: 16px; right: 16px; z-index: 100;">
        <button type="button" class="portal-theme-toggle-btn" onclick="toggleAppTheme()" aria-label="Ganti Mode Tampilan (Terang / Gelap)" title="Ganti Mode Tampilan">
            <i class="fas fa-sun theme-icon-light"></i>
            <i class="fas fa-moon theme-icon-dark"></i>
        </button>
    </div>

    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <div class="login-logos">
                    @php
                        $l1 = \App\Models\Setting::getLogo1Url();
                        $l2 = \App\Models\Setting::getLogo2Url();
                    @endphp
                    @if ($l1)
                        <img src="{{ $l1 }}" alt="Logo 1">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-university"></i></span>
                    @endif

                    @if ($l2)
                        <img src="{{ $l2 }}" alt="Logo 2">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-laptop-code"></i></span>
                    @endif
                </div>

                <div class="login-title">
                    <h1>{{ config('himati.app_name') }}</h1>
                    <p>Portal Pengurus — Politeknik Hasnur</p>
                </div>

                @if(session('info'))
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if (isset($errors) && $errors->any())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form action="{{ route('portal.login.submit') }}" method="POST" id="loginForm">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope"></i> Email Pengurus
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-control"
                               placeholder="nama@himati.id"
                               required
                               autocomplete="email"
                               value="{{ old('email') }}">
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
                                   placeholder="Masukkan password akun"
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

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: var(--space-3);">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: var(--font-sm); color: var(--text-secondary);">
                            <input type="checkbox" name="remember" value="1"> Ingat saya
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: var(--space-6);">
                        <i class="fas fa-sign-in-alt"></i>
                        Masuk Portal Pengurus
                    </button>
                </form>

                <div style="text-align: center; margin-top: var(--space-6); padding-top: var(--space-4); border-top: 1px solid var(--border-color);">
                    <a href="{{ route('login') }}" style="color: var(--primary); font-size: var(--font-sm); text-decoration: none;">
                        <i class="fas fa-user-shield"></i> Login Admin
                    </a>
                    <p style="font-size: var(--font-xs); color: var(--text-muted); margin-top: var(--space-3);">
                        <i class="fas fa-shield-alt"></i> &copy; {{ date('Y') }} Politeknik Hasnur
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
    </script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
