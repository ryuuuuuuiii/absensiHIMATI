<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#F4FAFF">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#071320">
    <title>@yield('title', 'Absensi HIMA TI') — {{ config('himati.app_name') }}</title>
    
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

    @if (auth('member')->check())
    <script type="speculationrules">
    {
        "prefetch": [
            {
                "source": "list",
                "urls": [
                    "{{ route('portal.dashboard') }}",
                    "{{ route('public.meeting.create') }}",
                    "{{ route('portal.qr') }}",
                    "{{ route('portal.attendance') }}",
                    "{{ route('portal.profile') }}"
                ],
                "eagerness": "moderate"
            }
        ]
    }
    </script>
    @endif

    @stack('styles')
</head>
<body>
    <div class="bg-animated"></div>
    
    <div class="public-page">
        <div class="public-container" style="max-width: @yield('container_width', '700px');">
            <!-- Compact Minimalist Header -->
            <div class="public-card public-brand-header">
                <div class="public-brand-logos">
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
                        <span class="logo-placeholder"><i class="fas fa-graduation-cap"></i></span>
                    @endif
                </div>
                <div class="public-brand-text">
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
            @if(session('danger') || session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('danger') ?? session('error') }}</span>
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
                <div class="footer-links">
                    @if (auth('web')->check())
                        <a href="{{ route('dashboard') }}"><i class="fas fa-th-large"></i> Dashboard Admin</a>
                        <button type="button" onclick="openPublicLogoutModal('admin')" style="background: none; border: none; color: inherit; cursor: pointer; font-size: inherit; font-family: inherit; display: inline-flex; align-items: center; gap: 6px; padding: 0;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    @elseif (auth('member')->check())
                        <a href="{{ route('portal.dashboard') }}"><i class="fas fa-th-large"></i> Portal Pengurus</a>
                        <button type="button" onclick="openPublicLogoutModal('member')" style="background: none; border: none; color: inherit; cursor: pointer; font-size: inherit; font-family: inherit; display: inline-flex; align-items: center; gap: 6px; padding: 0;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    @else
                        @if (! request()->routeIs('public.meeting.create'))
                            <a href="{{ route('public.meeting.create') }}"><i class="fas fa-calendar-plus"></i> Buat Rapat</a>
                        @endif
                        @if (! request()->routeIs('login'))
                            <a href="{{ route('login') }}"><i class="fas fa-sign-in-alt"></i> Login</a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @if (auth('member')->check())
        <!-- Portal Page Transition Loader -->
        <div id="portal-page-loader" class="portal-page-loader" aria-hidden="true">
            <div class="portal-loader-pill">
                <span class="portal-loader-spinner"></span>
                <span class="portal-loader-text">Memuat...</span>
            </div>
        </div>

        <!-- Bottom Navigation Bar Khusus Mode HP (Mobile View) -->
        <nav class="portal-bottom-nav" aria-label="Navigasi Bawah Mobile">
            <a href="{{ route('portal.dashboard') }}" class="portal-nav-tab tab-beranda {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                <div class="tab-icon-bubble">
                    <i class="fas fa-th-large"></i>
                </div>
                <span>Beranda</span>
            </a>
            <a href="{{ route('portal.qr') }}" class="portal-nav-tab tab-qr {{ request()->routeIs('portal.qr*') ? 'active' : '' }}">
                <div class="tab-icon-bubble">
                    <i class="fas fa-qrcode"></i>
                </div>
                <span>QR Saya</span>
            </a>
            <a href="{{ route('public.meeting.create') }}" class="portal-nav-tab tab-highlight {{ request()->routeIs('public.meeting.create') ? 'active' : '' }}" title="Buat Rapat Baru">
                <div class="tab-icon-bubble">
                    <i class="fas fa-plus"></i>
                </div>
                <span>Buat Rapat</span>
            </a>
            <a href="{{ route('portal.attendance') }}" class="portal-nav-tab tab-riwayat {{ request()->routeIs('portal.attendance*') || request()->routeIs('portal.absence*') ? 'active' : '' }}">
                <div class="tab-icon-bubble">
                    <i class="fas fa-history"></i>
                </div>
                <span>Riwayat</span>
            </a>
            <a href="{{ route('portal.profile') }}" class="portal-nav-tab tab-profil {{ request()->routeIs('portal.profile*') ? 'active' : '' }}">
                <div class="tab-icon-bubble">
                    <i class="fas fa-user-circle"></i>
                </div>
                <span>Profil</span>
            </a>
        </nav>
    @endif

    @if (auth('web')->check() || auth('member')->check())
        <!-- Modal Konfirmasi Logout Public -->
        <div class="logout-modal-overlay" id="publicLogoutModal" onclick="closePublicLogoutModal(event)">
            <div class="logout-modal-dialog" onclick="event.stopPropagation()">
                <div class="logout-modal-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3 class="logout-modal-title">Konfirmasi Keluar</h3>
                <p class="logout-modal-desc" id="publicLogoutDesc">
                    Apakah Anda yakin ingin keluar dari akun Anda?
                </p>
                <div class="logout-modal-actions">
                    <button type="button" class="btn btn-secondary logout-modal-btn-cancel" onclick="closePublicLogoutModal()">
                        Batal
                    </button>
                    <form id="publicLogoutForm" action="{{ auth('web')->check() ? route('logout') : route('portal.logout') }}" method="POST" style="margin: 0; flex: 1; display: flex;">
                        @csrf
                        <button type="submit" class="logout-modal-btn-confirm" style="width: 100%;">
                            <i class="fas fa-sign-out-alt"></i> Ya, Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div id="toastContainer" class="toast-container"></div>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script>
        window.BASE_URL = "{{ url('/') }}";
        window.CSRF_TOKEN = "{{ csrf_token() }}";

        function openPublicLogoutModal(type) {
            const modal = document.getElementById('publicLogoutModal');
            const form = document.getElementById('publicLogoutForm');
            const desc = document.getElementById('publicLogoutDesc');
            if (modal) {
                if (type === 'admin') {
                    if (form) form.action = "{{ route('logout') }}";
                    if (desc) desc.textContent = "Apakah Anda yakin ingin keluar dari Dashboard Administrator HIMA TI?";
                } else {
                    if (form) form.action = "{{ route('portal.logout') }}";
                    if (desc) desc.textContent = "Apakah Anda yakin ingin keluar dari akun Portal Pengurus?";
                }
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closePublicLogoutModal(event) {
            if (event && event.target && event.target.id !== 'publicLogoutModal') {
                return;
            }
            const modal = document.getElementById('publicLogoutModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePublicLogoutModal();
            }
        });

        @if (auth('member')->check())
        document.addEventListener('DOMContentLoaded', function() {
            const navTabs = document.querySelectorAll('.portal-nav-tab');
            const loader = document.getElementById('portal-page-loader');

            function showLoader() {
                if (!loader) return;
                loader.classList.add('loading');
            }

            function hideLoader() {
                if (!loader) return;
                loader.classList.remove('loading');
            }

            const prefetchTabs = new Set();
            function prefetchDoc(url) {
                if (!url || prefetchTabs.has(url)) return;
                try {
                    const u = new URL(url, window.location.origin);
                    if (u.origin !== window.location.origin) return;
                    prefetchTabs.add(url);
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = url;
                    link.as = 'document';
                    document.head.appendChild(link);
                } catch(e) {}
            }

            // Hover & touch prefetch on navigation tabs
            navTabs.forEach(function(tab) {
                const href = tab.getAttribute('href');
                if (href) {
                    tab.addEventListener('mouseover', function() { prefetchDoc(href); }, { passive: true });
                    tab.addEventListener('touchstart', function() { prefetchDoc(href); }, { passive: true });
                }

                tab.addEventListener('click', function() {
                    const href = this.getAttribute('href');
                    if (!href || href === '#' || href.startsWith('javascript:')) return;
                    navTabs.forEach(function(t) { t.classList.remove('active'); });
                    this.classList.add('active');
                    showLoader();
                });
            });

            // Also prefetch on footer links
            document.querySelectorAll('.public-footer a').forEach(function(el) {
                const href = el.getAttribute('href');
                if (href) {
                    el.addEventListener('mouseover', function() { prefetchDoc(href); }, { passive: true });
                    el.addEventListener('touchstart', function() { prefetchDoc(href); }, { passive: true });
                    el.addEventListener('click', function() { showLoader(); });
                }
            });

            // Proactive idle prefetch for immediate switching back to portal
            const PORTAL_PREFETCH = [
                '{{ route("portal.dashboard") }}',
                '{{ route("portal.qr") }}',
                '{{ route("portal.attendance") }}',
                '{{ route("portal.profile") }}'
            ];
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    PORTAL_PREFETCH.forEach(u => prefetchDoc(u));
                }, { timeout: 1500 });
            } else {
                setTimeout(() => {
                    PORTAL_PREFETCH.forEach(u => prefetchDoc(u));
                }, 800);
            }

            window.addEventListener('pageshow', function() {
                hideLoader();
            });
        });
        @endif
    </script>
    @stack('scripts')
</body>
</html>
