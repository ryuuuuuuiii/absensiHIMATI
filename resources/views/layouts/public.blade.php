<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Absensi HIMA TI') — {{ config('himati.app_name') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo1.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    
    @stack('styles')
</head>
<body>
    <div class="bg-animated"></div>
    
    <div class="public-page">
        <div class="public-container" style="max-width: @yield('container_width', '700px');">
            <!-- Header Logos -->
            <div class="public-card" style="margin-bottom: var(--space-6);">
                <div class="login-logos" style="margin-bottom: var(--space-4);">
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
                        <span class="logo-placeholder"><i class="fas fa-graduation-cap"></i></span>
                    @endif

                    @if ($l3 && file_exists(public_path('uploads/logos/' . $l3)))
                        <img src="{{ asset('uploads/logos/' . $l3) }}" alt="Logo 3">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-users"></i></span>
                    @endif
                </div>
                <div class="public-header-text">
                    <h1>@yield('page_heading', config('himati.app_full_name'))</h1>
                    <p>@yield('page_subheading', 'Sistem Absensi Digital Himpunan Mahasiswa Teknologi Informasi')</p>
                </div>
            </div>

            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('danger'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('danger') }}</span>
                </div>
            @endif
            @if(session('info'))
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Perhatian:</strong>
                        <ul style="margin: 4px 0 0 16px; padding: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
            
            <div class="public-footer">
                <p>&copy; {{ date('Y') }} HIMA TI Politeknik Hasnur • All Rights Reserved</p>
                <div class="footer-links" style="margin-top: 8px;">
                    <a href="{{ route('public.my_qr') }}"><i class="fas fa-qrcode"></i> Cari QR Anggota</a> •
                    <a href="{{ route('public.meeting.create') }}"><i class="fas fa-calendar-plus"></i> Buat Rapat</a> •
                    <a href="{{ route('login') }}"><i class="fas fa-lock"></i> Login Admin</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script>
        window.BASE_URL = "{{ url('/') }}";
        window.CSRF_TOKEN = "{{ csrf_token() }}";
    </script>
    @stack('scripts')
</body>
</html>
