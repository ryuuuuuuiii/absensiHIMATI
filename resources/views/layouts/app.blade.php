<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Absensi Digital HIMA TI Politeknik Hasnur">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Admin HIMA TI">
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
    
    @stack('styles')
</head>
<body>
    <div class="bg-animated"></div>

    @php
        $logo1 = \App\Models\Setting::getLogo1Url();
        $logo2 = \App\Models\Setting::getLogo2Url();
    @endphp
    
    <div class="app-wrapper admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logos">
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
                <button type="button" class="sidebar-close-btn" onclick="toggleSidebar(event)" aria-label="Tutup Menu">
                    <i class="fas fa-times"></i>
                </button>
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
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar(event)"></div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <header class="app-header">
                <div class="header-left">
                    {{-- Di mobile (karena sidebar disembunyikan di HP), tampilkan brand logo HIMA TI persis seperti di portal --}}
                    <a href="{{ route('dashboard') }}" class="portal-nav-brand admin-nav-brand" title="HIMA TI — Panel Admin">
                        <div class="portal-nav-logos">
                            @if ($logo1)
                                <img src="{{ $logo1 }}" alt="Logo Politeknik Hasnur" class="portal-nav-logo">
                            @else
                                <span class="portal-nav-logo-placeholder"><i class="fas fa-university"></i></span>
                            @endif

                            @if ($logo2)
                                <img src="{{ $logo2 }}" alt="Logo HIMA TI" class="portal-nav-logo">
                            @else
                                <span class="portal-nav-logo-placeholder"><i class="fas fa-graduation-cap"></i></span>
                            @endif
                        </div>
                        <div class="portal-nav-text">
                            <span class="portal-nav-title">HIMA TI</span>
                            <span class="portal-nav-subtitle">Politeknik Hasnur</span>
                        </div>
                    </a>

                    {{-- Di desktop (karena sidebar sudah ada logo HIMA TI), tampilkan nama menu aktif --}}
                    <div class="page-title app-header-page-title">
                        @yield('page_icon')
                        <span class="header-page-text">@yield('title', 'Dashboard')</span>
                    </div>
                </div>
                <div class="header-right">
                    <!-- Tombol Ganti Mode Tampilan (Terang / Gelap) -->
                    <button type="button" class="portal-theme-toggle-btn" id="adminHeaderThemeBtn" onclick="toggleAppTheme()" aria-label="Ganti Mode Tampilan (Terang / Gelap)" title="Ganti Mode Tampilan" style="margin-right: 4px;">
                        <i class="fas fa-sun theme-icon-light"></i>
                        <i class="fas fa-moon theme-icon-dark"></i>
                    </button>

                    @php
                        $pendingFeedbackCount = \App\Models\Feedback::where('status', 'pending')->count();
                    @endphp
                    @if($pendingFeedbackCount > 0)
                        <a href="{{ route('feedbacks.index') }}" class="header-action-btn" title="{{ $pendingFeedbackCount }} Feedback Perlu Ditinjau">
                            <i class="fas fa-comment-dots"></i>
                            <span class="header-badge-count">{{ $pendingFeedbackCount }}</span>
                        </a>
                    @endif
                    <div class="header-user" onclick="openAdminLogoutModal()" style="cursor: pointer;" title="Klik untuk keluar / akun">
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

            <!-- In-Page Header for Mobile (Shows current page title under navbar) -->
            <div class="portal-page-header admin-page-header">
                <h1 class="portal-page-title">
                    @hasSection('page_icon')
                        @yield('page_icon')
                    @else
                        <i class="fas fa-th-large"></i>
                    @endif
                    <span>@yield('title', 'Dashboard')</span>
                </h1>
            </div>
            
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
    
    <!-- Admin Mobile Web App Bottom Navigation Bar -->
    <nav class="portal-bottom-nav admin-bottom-nav" aria-label="Navigasi Bawah Admin">
        <a href="{{ route('dashboard') }}" class="portal-nav-tab tab-beranda {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <div class="tab-icon-bubble">
                <i class="fas fa-th-large"></i>
            </div>
            <span>Beranda</span>
        </a>
        <a href="{{ route('members.index') }}" class="portal-nav-tab tab-anggota {{ request()->routeIs('members.*') && !request()->routeIs('members.generate_qr*') && !request()->routeIs('members.accounts*') ? 'active' : '' }}">
            <div class="tab-icon-bubble">
                <i class="fas fa-users-cog"></i>
            </div>
            <span>Anggota</span>
        </a>
        <a href="{{ route('meetings.index') }}" class="portal-nav-tab tab-highlight {{ request()->routeIs('meetings.*') ? 'active' : '' }}" title="Kelola Rapat">
            <div class="tab-icon-bubble">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <span>Rapat</span>
        </a>
        <a href="{{ route('reports.index') }}" class="portal-nav-tab tab-laporan {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <div class="tab-icon-bubble">
                <i class="fas fa-chart-bar"></i>
            </div>
            <span>Laporan</span>
        </a>
        <button type="button" class="portal-nav-tab tab-menu {{ request()->routeIs('events.*') || request()->routeIs('announcements.*') || request()->routeIs('feedbacks.*') || request()->routeIs('members.generate_qr*') || request()->routeIs('members.accounts*') || request()->routeIs('settings.*') ? 'active' : '' }}" onclick="openAdminMenuSheet();" aria-label="Buka Menu Admin" style="background: none; border: none; cursor: pointer; padding: 0;">
            <div class="tab-icon-bubble" style="position: relative;">
                <i class="fas fa-th"></i>
                @if(isset($pendingFeedbackCount) && $pendingFeedbackCount > 0)
                    <span class="nav-tab-badge-dot"></span>
                @endif
            </div>
            <span>Menu</span>
        </button>
    </nav>
    
    <!-- Admin Mobile Menu Bottom Sheet -->
    <div class="admin-menu-sheet-overlay" id="adminMenuSheet" onclick="closeAdminMenuSheet(event)">
        <div class="admin-menu-sheet-dialog" onclick="event.stopPropagation()">
            <div class="admin-menu-sheet-handle"></div>
            <div class="admin-menu-sheet-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-th-large" style="color: var(--blue-primary); font-size: 1.1rem;"></i>
                    <strong style="font-size: 1rem; color: var(--text-primary);">Menu Administrator</strong>
                </div>
                <button type="button" class="admin-menu-sheet-close" onclick="closeAdminMenuSheet()" aria-label="Tutup Menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="admin-menu-sheet-grid">
                <a href="{{ route('events.index') }}" class="menu-sheet-item {{ request()->routeIs('events.*') ? 'active' : '' }}">
                    <div class="menu-sheet-icon" style="background: rgba(168, 85, 247, 0.15); color: #A855F7;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <span>Kelola Event</span>
                </a>
                <a href="{{ route('announcements.index') }}" class="menu-sheet-item {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                    <div class="menu-sheet-icon" style="background: rgba(59, 130, 246, 0.15); color: #3B82F6;">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <span>Pengumuman</span>
                </a>
                <a href="{{ route('feedbacks.index') }}" class="menu-sheet-item {{ request()->routeIs('feedbacks.*') ? 'active' : '' }}" style="position: relative;">
                    <div class="menu-sheet-icon" style="background: rgba(245, 158, 11, 0.15); color: #F59E0B;">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <span>Feedback</span>
                    @if(isset($pendingFeedbackCount) && $pendingFeedbackCount > 0)
                        <span class="sheet-badge-count">{{ $pendingFeedbackCount }}</span>
                    @endif
                </a>
                <a href="{{ route('members.generate_qr') }}" class="menu-sheet-item {{ request()->routeIs('members.generate_qr*') ? 'active' : '' }}">
                    <div class="menu-sheet-icon" style="background: rgba(16, 185, 129, 0.15); color: #10B981;">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <span>Generate QR</span>
                </a>
                <a href="{{ route('members.accounts') }}" class="menu-sheet-item {{ request()->routeIs('members.accounts*') ? 'active' : '' }}">
                    <div class="menu-sheet-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366F1;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <span>Akun Pengurus</span>
                </a>
                <a href="{{ route('settings.index') }}" class="menu-sheet-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <div class="menu-sheet-icon" style="background: rgba(100, 116, 139, 0.15); color: #64748B;">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <span>Pengaturan</span>
                </a>
                <a href="{{ route('portal.dashboard') }}" class="menu-sheet-item" target="_blank">
                    <div class="menu-sheet-icon" style="background: rgba(6, 182, 212, 0.15); color: #06B6D4;">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <span>Portal</span>
                </a>
                <a href="{{ route('public.meeting.create') }}" class="menu-sheet-item" target="_blank">
                    <div class="menu-sheet-icon" style="background: rgba(234, 88, 12, 0.15); color: #EA580C;">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <span>Buat Rapat</span>
                </a>
                <button type="button" class="menu-sheet-item menu-sheet-logout" onclick="closeAdminMenuSheet(); openAdminLogoutModal();">
                    <div class="menu-sheet-icon" style="background: rgba(239, 68, 68, 0.15); color: #EF4444;">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <span>Logout</span>
                </button>
            </div>
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

        function openAdminMenuSheet() {
            const sheet = document.getElementById('adminMenuSheet');
            if (sheet) {
                sheet.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeAdminMenuSheet(event) {
            if (event && event.target && event.target.id !== 'adminMenuSheet' && !event.target.closest('.admin-menu-sheet-close')) {
                return;
            }
            const sheet = document.getElementById('adminMenuSheet');
            if (sheet) {
                sheet.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function openAdminLogoutModal() {
            closeAdminMenuSheet();
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            }
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
                closeAdminMenuSheet();
                closeAdminLogoutModal();
            }
        });
    </script>
    @include('partials.popup_alert')
    @stack('scripts')
</body>
</html>
