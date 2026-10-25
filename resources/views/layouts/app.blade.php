<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Absensi Digital HIMA TI Politeknik Hasnur">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('himati.app_name') }}</title>
    
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
    
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logos">
                    @php
                        $logo1 = \App\Models\Setting::get('logo_1');
                        $logo2 = \App\Models\Setting::get('logo_2');
                        $logo3 = \App\Models\Setting::get('logo_3');
                    @endphp
                    @if ($logo1 && file_exists(public_path('uploads/logos/' . $logo1)))
                        <img src="{{ asset('uploads/logos/' . $logo1) }}" alt="Logo 1">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-university"></i></span>
                    @endif

                    @if ($logo2 && file_exists(public_path('uploads/logos/' . $logo2)))
                        <img src="{{ asset('uploads/logos/' . $logo2) }}" alt="Logo 2">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-graduation-cap"></i></span>
                    @endif

                    @if ($logo3 && file_exists(public_path('uploads/logos/' . $logo3)))
                        <img src="{{ asset('uploads/logos/' . $logo3) }}" alt="Logo 3">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-users"></i></span>
                    @endif
                </div>
                <div class="sidebar-brand">
                    {{ config('himati.app_name') }}
                    <small>Panel Admin</small>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Menu Utama</div>
                    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Kelola</div>
                    <a href="{{ route('members.index') }}" class="nav-item {{ request()->routeIs('members.*') && !request()->routeIs('members.generate_qr*') ? 'active' : '' }}">
                        <i class="fas fa-users-cog"></i>
                        <span>Kelola Anggota</span>
                    </a>
                    <a href="{{ route('events.index') }}" class="nav-item {{ request()->routeIs('events.*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Kelola Event</span>
                    </a>
                    <a href="{{ route('meetings.index') }}" class="nav-item {{ request()->routeIs('meetings.*') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Kelola Rapat</span>
                    </a>
                    <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar"></i>
                        <span>Laporan</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Pengaturan</div>
                    <a href="{{ route('members.generate_qr') }}" class="nav-item {{ request()->routeIs('members.generate_qr*') ? 'active' : '' }}">
                        <i class="fas fa-qrcode"></i>
                        <span>Generate QR Code</span>
                    </a>
                    <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i class="fas fa-cogs"></i>
                        <span>Pengaturan</span>
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <a href="{{ route('public.meeting.create') }}" class="nav-item" target="_blank" style="color: var(--primary);">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Link Buat Rapat</span>
                </a>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="nav-item" style="color: var(--danger); background: none; border: none; width: 100%; text-align: left; cursor: pointer;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>
        
        <!-- Mobile Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <header class="app-header">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">
                        @yield('page_icon')
                        @yield('title', 'Dashboard')
                    </div>
                </div>
                <div class="header-right">
                    <a href="{{ route('public.my_qr') }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="margin-right: var(--space-3);" title="Cari QR Anggota">
                        <i class="fas fa-id-badge"></i> My QR
                    </a>
                    <div class="header-user">
                        <div class="header-user-avatar">
                            {{ auth()->check() ? substr(auth()->user()->nama, 0, 1) : 'A' }}
                        </div>
                        <div class="header-user-info">
                            <span class="header-user-name">{{ auth()->user()->nama ?? 'Administrator' }}</span>
                            <span class="header-user-role">Administrator</span>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="toast-container" id="toastContainer"></div>
            
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
            @if(session('warning'))
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif
            @if(session('info'))
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Terjadi kesalahan:</strong>
                        <ul style="margin: 4px 0 0 16px; padding: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            
            <!-- Page Body -->
            @yield('content')
        </div>
    </div>
    
    <!-- Core JS -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script>
        window.BASE_URL = "{{ url('/') }}";
        window.CSRF_TOKEN = "{{ csrf_token() }}";
    </script>
    @stack('scripts')
</body>
</html>
