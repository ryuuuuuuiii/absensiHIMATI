<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Absensi Digital HIMA TI Politeknik Hasnur">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#F4FAFF">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#071320">
    <title>@yield('title', 'Dashboard') — {{ config('himati.app_name') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ \App\Models\Setting::getLogo2Url() ?? asset('assets/img/logo2.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wdth,wght@75..125,500..700&family=Noto+Sans:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ file_exists(public_path('assets/css/style.css')) ? filemtime(public_path('assets/css/style.css')) : time() }}">

    <script>
        (function() {
            try { localStorage.removeItem('himati_theme'); } catch(e) {}
            function applyDeviceTheme(e) {
                var isDark = (e && typeof e.matches === 'boolean') 
                    ? e.matches 
                    : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
            }
            applyDeviceTheme();
            try {
                var mq = window.matchMedia('(prefers-color-scheme: dark)');
                if (mq.addEventListener) {
                    mq.addEventListener('change', applyDeviceTheme);
                } else if (mq.addListener) {
                    mq.addListener(applyDeviceTheme);
                }
            } catch(e) {}
        })();
    </script>
    
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
                        $logo1 = \App\Models\Setting::getLogo1Url();
                        $logo2 = \App\Models\Setting::getLogo2Url();
                    @endphp
                    @if ($logo1)
                        <img src="{{ $logo1 }}" alt="Logo Politeknik Hasnur" class="sidebar-logo-img">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-university"></i></span>
                    @endif

                    @if ($logo2)
                        <img src="{{ $logo2 }}" alt="Logo HIMA TI" class="sidebar-logo-img">
                    @else
                        <span class="logo-placeholder"><i class="fas fa-graduation-cap"></i></span>
                    @endif
                </div>
                <div class="sidebar-brand">
                    <span class="sidebar-brand-title">HIMA TI</span>
                    <span class="sidebar-brand-sub">Panel Admin</span>
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
                    <a href="{{ route('announcements.index') }}" class="nav-item {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                        <i class="fas fa-bullhorn"></i>
                        <span>Pengumuman & Pesan</span>
                    </a>
                    <a href="{{ route('feedbacks.index') }}" class="nav-item {{ request()->routeIs('feedbacks.*') ? 'active' : '' }}">
                        <i class="fas fa-comment-dots"></i>
                        <span>Kotak Feedback</span>
                        @php
                            $pendingFeedbackCount = \App\Models\Feedback::where('status', 'pending')->count();
                        @endphp
                        @if($pendingFeedbackCount > 0)
                            <span class="badge badge-warning" style="margin-left: auto; font-size: 0.68rem; padding: 2px 7px; border-radius: 999px;">{{ $pendingFeedbackCount }}</span>
                        @endif
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Pengaturan</div>
                    <a href="{{ route('members.generate_qr') }}" class="nav-item {{ request()->routeIs('members.generate_qr*') ? 'active' : '' }}">
                        <i class="fas fa-qrcode"></i>
                        <span>Generate QR Code</span>
                    </a>
                    <a href="{{ route('members.accounts') }}" class="nav-item {{ request()->routeIs('members.accounts*') ? 'active' : '' }}">
                        <i class="fas fa-user-shield"></i>
                        <span>Akun Pengurus</span>
                    </a>
                    <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i class="fas fa-cogs"></i>
                        <span>Pengaturan</span>
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <a href="{{ route('portal.dashboard') }}" class="nav-item" target="_blank" style="color: var(--primary);">
                    <i class="fas fa-user-circle"></i>
                    <span>Portal Pengurus</span>
                </a>
                <a href="{{ route('public.meeting.create') }}" class="nav-item" target="_blank" style="color: var(--primary);">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Link Buat Rapat</span>
                </a>
                <button type="button" class="nav-item" onclick="openAdminLogoutModal()" style="color: var(--danger); background: none; border: none; width: 100%; text-align: left; cursor: pointer;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
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
                        <span class="header-page-text">@yield('title', 'Dashboard')</span>
                    </div>
                </div>
                <div class="header-right">
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
            @if(session('danger') || session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('danger') ?? session('error') }}</span>
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
    
    <!-- Modal Konfirmasi Logout Admin -->
    <div class="logout-modal-overlay" id="adminLogoutModal" onclick="closeAdminLogoutModal(event)">
        <div class="logout-modal-dialog" onclick="event.stopPropagation()">
            <div class="logout-modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3 class="logout-modal-title">Konfirmasi Logout</h3>
            <p class="logout-modal-desc">
                Apakah Anda yakin ingin keluar dari Dashboard Administrator HIMA TI?
            </p>
            <div class="logout-modal-actions">
                <button type="button" class="btn btn-secondary logout-modal-btn-cancel" onclick="closeAdminLogoutModal()">
                    Batal
                </button>
                <form id="adminLogoutForm" action="{{ route('logout') }}" method="POST" style="margin: 0; flex: 1; display: flex;">
                    @csrf
                    <button type="submit" class="logout-modal-btn-confirm" style="width: 100%;">
                        <i class="fas fa-sign-out-alt"></i> Ya, Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script>
        window.BASE_URL = "{{ url('/') }}";
        window.CSRF_TOKEN = "{{ csrf_token() }}";

        function openAdminLogoutModal() {
            const modal = document.getElementById('adminLogoutModal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeAdminLogoutModal(event) {
            if (event && event.target && event.target.id !== 'adminLogoutModal') {
                return;
            }
            const modal = document.getElementById('adminLogoutModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAdminLogoutModal();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
