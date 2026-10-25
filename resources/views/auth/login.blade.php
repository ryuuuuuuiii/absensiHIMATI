<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login Admin Sistem Absensi HIMA TI Politeknik Hasnur">
    <title>Login Admin — {{ config('himati.app_full_name') }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ file_exists(public_path('assets/css/style.css')) ? filemtime(public_path('assets/css/style.css')) : time() }}">
</head>
<body>
    <div class="bg-animated"></div>
    
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <!-- 3 Logo Space -->
                <div class="login-logos">
                    @php
                        $l1 = \App\Models\Setting::get('logo_1');
                        $l2 = \App\Models\Setting::get('logo_2');
                        $l3 = \App\Models\Setting::get('logo_3');
                    @endphp
                    @if ($l1 && file_exists(public_path('uploads/logos/' . $l1)))
                        <img src="{{ asset('uploads/logos/' . $l1) }}" alt="Logo 1">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-university"></i></span>
                    @endif

                    @if ($l2 && file_exists(public_path('uploads/logos/' . $l2)))
                        <img src="{{ asset('uploads/logos/' . $l2) }}" alt="Logo 2">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-laptop-code"></i></span>
                    @endif

                    @if ($l3 && file_exists(public_path('uploads/logos/' . $l3)))
                        <img src="{{ asset('uploads/logos/' . $l3) }}" alt="Logo 3">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-users"></i></span>
                    @endif
                </div>
                
                <!-- Title -->
                <div class="login-title">
                    <h1>{{ config('himati.app_name') }}</h1>
                    <p>Panel Admin — Politeknik Hasnur</p>
                </div>
                
                <!-- Flash Alerts -->
                @if(session('info'))
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif
                @if (isset($errors) && $errors->any())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif
                
                <!-- Login Form -->
                <form action="{{ route('login.submit') }}" method="POST" id="loginForm">
                    @csrf
                    
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
                               value="{{ old('email', 'admin@himati.id') }}">
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

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: var(--space-3);">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: var(--font-sm); color: var(--text-secondary);">
                            <input type="checkbox" name="remember" value="1"> Ingat saya
                        </label>
                        <a href="{{ route('public.my_qr') }}" style="color: var(--primary); font-size: var(--font-sm); text-decoration: none;">
                            Cari QR Saya
                        </a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: var(--space-6);">
                        <i class="fas fa-sign-in-alt"></i>
                        Masuk Panel Admin
                    </button>
                </form>
                
                <!-- Public Link -->
                <div style="text-align: center; margin-top: var(--space-6); padding-top: var(--space-4); border-top: 1px solid var(--dark-border);">
                    <a href="{{ route('public.meeting.create') }}" style="color: var(--primary); font-size: var(--font-sm); text-decoration: none;">
                        <i class="fas fa-external-link-alt"></i> Buat Rapat (Halaman Publik)
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
</body>
</html>
