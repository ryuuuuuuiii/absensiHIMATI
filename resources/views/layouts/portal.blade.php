<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal Pengurus HIMA TI Politeknik Hasnur">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#F4FAFF">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#071320">
    <title>@yield('title', 'Portal Pengurus') — {{ config('himati.app_name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ \App\Models\Setting::getLogo2Url() ?? asset('assets/img/logo2.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.cdnfonts.com/css/gliker" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wdth,wght@75..125,500..700&family=Noto+Sans:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
                    "{{ route('portal.profile') }}",
                    "{{ route('portal.announcements.index') }}"
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

    @php
        $portalMember = auth('member')->user();
    @endphp

    <div class="app-wrapper">
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
                    <span class="sidebar-brand-sub">Portal Pengurus</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Menu Utama</div>
                    <a href="{{ route('portal.dashboard') }}" class="nav-item {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('portal.qr') }}" class="nav-item {{ request()->routeIs('portal.qr*') ? 'active' : '' }}">
                        <i class="fas fa-qrcode"></i>
                        <span>QR Saya</span>
                    </a>
                    <a href="{{ route('public.meeting.create') }}" class="nav-item {{ request()->routeIs('public.meeting.create') ? 'active' : '' }}">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Buat Rapat</span>
                    </a>
                    <a href="{{ route('portal.attendance') }}" class="nav-item {{ request()->routeIs('portal.attendance*') || request()->routeIs('portal.absence*') ? 'active' : '' }}">
                        <i class="fas fa-history"></i>
                        <span>Riwayat Absensi</span>
                    </a>
                    <a href="{{ route('portal.announcements.index') }}" class="nav-item {{ request()->routeIs('portal.announcements*') ? 'active' : '' }}">
                        <i class="fas fa-bullhorn"></i>
                        <span>Pengumuman</span>
                        @if (isset($portalUnreadNotifCount) && $portalUnreadNotifCount > 0)
                            <span class="portal-sidebar-badge">{{ $portalUnreadNotifCount > 9 ? '9+' : $portalUnreadNotifCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('portal.profile') }}" class="nav-item {{ request()->routeIs('portal.profile*') ? 'active' : '' }}">
                        <i class="fas fa-user-circle"></i>
                        <span>Profil Saya</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <button type="button" class="nav-item" onclick="openPortalLogoutModal()" style="color: var(--danger); background: none; border: none; width: 100%; text-align: left; cursor: pointer;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar (Logout)</span>
                </button>
            </div>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <div class="main-content">
            <!-- Portal Page Transition Loader (Smooth navigation feedback) -->
            <div id="portal-page-loader" class="portal-page-loader" aria-hidden="true">
                <div class="portal-loader-pill">
                    <span class="portal-loader-spinner"></span>
                    <span class="portal-loader-text">Memuat...</span>
                </div>
            </div>

            <header class="app-header portal-navbar">
                <div class="header-left">
                    {{-- Di mobile (karena sidebar disembunyikan), tampilkan brand logo HIMA TI --}}
                    <a href="{{ route('portal.dashboard') }}" class="portal-nav-brand" title="HIMA TI — {{ config('himati.app_name') }}">
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
                    <div class="portal-header-page-title page-title">
                        @hasSection('page_icon')
                            @yield('page_icon')
                        @else
                            <i class="fas fa-th-large"></i>
                        @endif
                        <span class="header-page-text">@yield('title', 'Dashboard')</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="portal-notif-wrapper">
                        <a href="{{ route('portal.announcements.index') }}" class="portal-notif-btn" aria-label="Notifikasi & Pengumuman" title="Notifikasi & Pengumuman">
                            <i class="fas fa-bell"></i>
                            @if(isset($portalUnreadNotifCount) && $portalUnreadNotifCount > 0)
                                <span class="portal-notif-badge" id="portalNotifBadge">{{ $portalUnreadNotifCount > 9 ? '9+' : $portalUnreadNotifCount }}</span>
                            @endif
                        </a>
                    </div>

                    <a href="{{ route('portal.profile') }}" class="header-avatar-link" title="Profil Saya">
                        <div class="header-avatar-circle">
                            @if ($portalMember?->profile_photo_url)
                                <img src="{{ $portalMember->profile_photo_url }}" alt="{{ $portalMember->nama_lengkap }}">
                            @else
                                {{ $portalMember?->initials ?? 'P' }}
                            @endif
                        </div>
                    </a>
                </div>
            </header>

            <main id="portal-viewport" class="portal-viewport">
                <div id="portal-content-layer" class="portal-content-layer">
                    <div class="toast-container" id="toastContainer"></div>

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

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Bottom Navigation Bar Khusus Mode HP (Mobile View) -->
    <nav class="portal-bottom-nav" aria-label="Navigasi Bawah Mobile">
        <svg class="portal-nav-fluid-svg" preserveAspectRatio="none" aria-hidden="true">
            <path class="portal-nav-fluid-fill" d=""></path>
            <path class="portal-nav-fluid-stroke" d=""></path>
        </svg>
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

    <!-- Modal Konfirmasi Logout Portal -->
    <div class="logout-modal-overlay" id="portalLogoutModal" onclick="closePortalLogoutModal(event)">
        <div class="logout-modal-dialog" onclick="event.stopPropagation()">
            <div class="logout-modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3 class="logout-modal-title">Konfirmasi Keluar</h3>
            <p class="logout-modal-desc">
                Apakah Anda yakin ingin keluar dari akun Portal Pengurus? Sesi Anda akan berakhir dan Anda dapat masuk kembali kapan saja.
            </p>
            <div class="logout-modal-actions">
                <button type="button" class="btn btn-secondary logout-modal-btn-cancel" onclick="closePortalLogoutModal()">
                    Batal
                </button>
                <form id="portalLogoutForm" action="{{ route('portal.logout') }}" method="POST" style="margin: 0; flex: 1; display: flex;">
                    @csrf
                    <button type="submit" class="logout-modal-btn-confirm" style="width: 100%;">
                        <i class="fas fa-sign-out-alt"></i> Ya, Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script>
        window.BASE_URL = "{{ url('/') }}";
        window.CSRF_TOKEN = "{{ csrf_token() }}";

        document.addEventListener('DOMContentLoaded', function() {
            const loader = document.getElementById('portal-page-loader');

            function showLoader() {
                if (loader) loader.classList.add('loading');
            }

            function hideLoader() {
                if (loader) loader.classList.remove('loading');
            }

            // ============================================
            // PORTAL SPA ENGINE (Cache + Slide Transitions)
            // ============================================
            const portalCache = new Map();
            let isNavigating = false;

            function normalizePath(urlPath) {
                try {
                    const u = new URL(urlPath, window.location.origin);
                    return u.pathname.replace(/\/+$/, '') || '/';
                } catch(e) {
                    return urlPath.replace(/\/+$/, '') || '/';
                }
            }

            function isPortalRoute(urlPath) {
                if (!urlPath) return false;
                const p = normalizePath(urlPath);
                return p.startsWith('/portal') || p === '/rapat' || p.startsWith('/rapat');
            }

            function getRouteIndex(path) {
                const p = normalizePath(path);
                if (p === '/portal') return 0;
                if (p.startsWith('/portal/qr')) return 1;
                if (p.startsWith('/portal/riwayat') || p.startsWith('/portal/absen')) return 2;
                if (p.startsWith('/portal/profil')) return 3;
                if (p.startsWith('/portal/notifikasi')) return 4;
                return 1;
            }

            // Cache current initial page immediately
            const initialLayer = document.getElementById('portal-content-layer');
            const initialTitleEl = document.querySelector('.portal-header-page-title .header-page-text');
            const initialIconEl = document.querySelector('.portal-header-page-title i');

            if (initialLayer) {
                const initialHeadStyles = Array.from(document.head.querySelectorAll('style')).map(s => s.outerHTML).join('\n');
                portalCache.set(normalizePath(window.location.pathname), {
                    html: initialLayer.innerHTML,
                    styles: initialHeadStyles,
                    title: document.title,
                    headerTitle: initialTitleEl ? initialTitleEl.textContent : '',
                    headerIconHtml: initialIconEl ? initialIconEl.outerHTML : '<i class="fas fa-th-large"></i>',
                    timestamp: Date.now()
                });
            }

            function updateHeaderMeta(data) {
                if (data.title) {
                    document.title = data.title;
                }
                const headerTitleEl = document.querySelector('.portal-header-page-title .header-page-text');
                const headerIconEl = document.querySelector('.portal-header-page-title i');
                if (headerTitleEl && data.headerTitle) {
                    headerTitleEl.textContent = data.headerTitle;
                }
                if (headerIconEl && data.headerIconHtml) {
                    headerIconEl.outerHTML = data.headerIconHtml;
                }
            }

            function updateNavHighlights(targetPath) {
                const clean = normalizePath(targetPath);

                // Update bottom nav tabs
                const bottomTabs = document.querySelectorAll('.portal-bottom-nav .portal-nav-tab');
                let matchedBottomTab = null;

                bottomTabs.forEach(tab => {
                    const href = tab.getAttribute('href');
                    if (!href) return;
                    const tabClean = normalizePath(href);
                    const isMatch = (clean === tabClean) || 
                        (tabClean !== '/portal' && clean.startsWith(tabClean)) ||
                        (tabClean === '/portal' && clean === '/portal');

                    if (isMatch) {
                        tab.classList.add('active');
                        matchedBottomTab = tab;
                    } else {
                        tab.classList.remove('active');
                    }
                });

                if (matchedBottomTab && typeof window.updateFluidNavPosition === 'function') {
                    window.updateFluidNavPosition(matchedBottomTab);
                }

                // Update sidebar items
                const sidebarItems = document.querySelectorAll('.sidebar-nav .nav-item');
                sidebarItems.forEach(item => {
                    const href = item.getAttribute('href');
                    if (!href) return;
                    const itemClean = normalizePath(href);
                    const isMatch = (clean === itemClean) || 
                        (itemClean !== '/portal' && clean.startsWith(itemClean)) ||
                        (itemClean === '/portal' && clean === '/portal');

                    if (isMatch) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });
            }

            function executeScripts(container) {
                const scripts = container.querySelectorAll('script');
                let pendingExternal = 0;

                function checkDone() {
                    if (pendingExternal === 0) {
                        document.dispatchEvent(new CustomEvent('portal:page-loaded'));
                    }
                }

                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent;

                    if (oldScript.src) {
                        pendingExternal++;
                        newScript.onload = () => {
                            pendingExternal--;
                            checkDone();
                        };
                        newScript.onerror = () => {
                            pendingExternal--;
                            checkDone();
                        };
                    }

                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

                checkDone();
            }

            async function fetchPageData(urlPath) {
                const res = await fetch(urlPath, {
                    headers: {
                        'Accept': 'text/html,application/xhtml+xml,application/xml',
                        'X-Portal-Spa': '1'
                    },
                    credentials: 'same-origin'
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const htmlText = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(htmlText, 'text/html');

                const newLayer = doc.getElementById('portal-content-layer');
                if (!newLayer) throw new Error('Missing #portal-content-layer in response');

                const headerTitleEl = doc.querySelector('.portal-header-page-title .header-page-text');
                const headerIconEl = doc.querySelector('.portal-header-page-title i');
                const headStyles = Array.from(doc.head.querySelectorAll('style')).map(s => s.outerHTML).join('\n');

                return {
                    html: newLayer.innerHTML,
                    styles: headStyles,
                    title: doc.title,
                    headerTitle: headerTitleEl ? headerTitleEl.textContent : '',
                    headerIconHtml: headerIconEl ? headerIconEl.outerHTML : '<i class="fas fa-th-large"></i>',
                    timestamp: Date.now()
                };
            }

            function performSwap(contentLayer, data) {
                return new Promise(resolve => {
                    contentLayer.style.opacity = '0.35';

                    requestAnimationFrame(() => {
                        contentLayer.innerHTML = data.html;
                        updateHeaderMeta(data);

                        if (data.styles !== undefined) {
                            let dynStyle = document.getElementById('portal-dynamic-styles');
                            if (!dynStyle) {
                                dynStyle = document.createElement('div');
                                dynStyle.id = 'portal-dynamic-styles';
                                document.head.appendChild(dynStyle);
                            }
                            dynStyle.innerHTML = data.styles;
                        }

                        // Reset scroll to top immediately
                        window.scrollTo({ top: 0, behavior: 'instant' });

                        // Execute script tags inside new content
                        executeScripts(contentLayer);

                        requestAnimationFrame(() => {
                            contentLayer.style.opacity = '1';
                            resolve();
                        });
                    });
                });
            }

            // Preload & Prefetch Engine
            const PRELOAD_ROUTES = [
                '{{ route("portal.dashboard") }}',
                '{{ route("public.meeting.create") }}',
                '{{ route("portal.qr") }}',
                '{{ route("portal.attendance") }}',
                '{{ route("portal.profile") }}',
                '{{ route("portal.announcements.index") }}'
            ];

            const prefetchingUrls = new Set();
            function prefetchPage(urlPath) {
                try {
                    const u = new URL(urlPath, window.location.origin);
                    if (u.origin !== window.location.origin) return;
                    if (!isPortalRoute(u.pathname)) return;
                    if (u.pathname.includes('/logout') || u.pathname.includes('/login') || u.pathname.includes('/qr/download')) return;

                    const clean = normalizePath(u.pathname);
                    if (portalCache.has(clean) || prefetchingUrls.has(clean)) return;

                    prefetchingUrls.add(clean);
                    fetchPageData(u.pathname + u.search).then(data => {
                        portalCache.set(clean, data);
                    }).catch(() => {}).finally(() => {
                        prefetchingUrls.delete(clean);
                    });
                } catch(e) {}
            }

            // Prefetch on hover (desktop)
            document.addEventListener('mouseover', function(e) {
                const link = e.target.closest('a');
                if (link && link.href) prefetchPage(link.href);
            }, { passive: true });

            // Prefetch on touchstart (mobile)
            document.addEventListener('touchstart', function(e) {
                const link = e.target.closest('a');
                if (link && link.href) prefetchPage(link.href);
            }, { passive: true });

            // Idle prefetch main portal tabs
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    PRELOAD_ROUTES.forEach(url => prefetchPage(url));
                }, { timeout: 2000 });
            } else {
                setTimeout(() => {
                    PRELOAD_ROUTES.forEach(url => prefetchPage(url));
                }, 1000);
            }

            async function revalidateCache(urlPath) {
                try {
                    const cleanPath = normalizePath(urlPath);
                    const freshData = await fetchPageData(urlPath);
                    const oldCached = portalCache.get(cleanPath);
                    portalCache.set(cleanPath, freshData);

                    // If user is currently viewing this exact page and content changed, update seamlessly
                    if (normalizePath(window.location.pathname) === cleanPath) {
                        if (oldCached && oldCached.html !== freshData.html) {
                            const layer = document.getElementById('portal-content-layer');
                            if (layer) {
                                layer.innerHTML = freshData.html;
                                executeScripts(layer);
                            }
                        }
                    }
                } catch(e) {
                    // Silently ignore background revalidation errors
                }
            }

            async function navigateTo(targetUrl, pushState = true) {
                if (isNavigating) return;
                isNavigating = true;

                const cleanTarget = normalizePath(targetUrl);

                // Immediate instant tactile UI feedback
                updateNavHighlights(targetUrl);

                const contentLayer = document.getElementById('portal-content-layer');
                if (!contentLayer) {
                    window.location.href = targetUrl;
                    return;
                }

                const cached = portalCache.get(cleanTarget);

                if (cached) {
                    // Instant swap from memory cache (<10ms!)
                    await performSwap(contentLayer, cached);
                    if (pushState) {
                        history.pushState({ spa: true, url: targetUrl }, cached.title, targetUrl);
                    }
                    isNavigating = false;
                    // Background refresh
                    revalidateCache(targetUrl);
                } else {
                    // First time uncached: show subtle top loader pill
                    showLoader();
                    try {
                        const freshData = await fetchPageData(targetUrl);
                        hideLoader();
                        portalCache.set(cleanTarget, freshData);
                        await performSwap(contentLayer, freshData);
                        if (pushState) {
                            history.pushState({ spa: true, url: targetUrl }, freshData.title, targetUrl);
                        }
                    } catch(err) {
                        console.error('SPA fetch error, fallback to normal navigation:', err);
                        window.location.href = targetUrl;
                        return;
                    } finally {
                        hideLoader();
                        isNavigating = false;
                    }
                }
            }

            // Intercept link clicks
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (!link) return;

                const href = link.getAttribute('href');
                if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('tel:') || href.startsWith('mailto:')) {
                    return;
                }
                if (link.getAttribute('target') === '_blank' || link.hasAttribute('download') || link.classList.contains('no-spa')) {
                    return;
                }

                let urlObj;
                try {
                    urlObj = new URL(href, window.location.origin);
                } catch(err) {
                    return;
                }

                // Check if internal portal or rapat link
                if (urlObj.origin !== window.location.origin) return;
                if (!isPortalRoute(urlObj.pathname)) return;

                // Exclude logout, login, or direct download routes
                if (urlObj.pathname.includes('/logout') || urlObj.pathname.includes('/login') || urlObj.pathname.includes('/qr/download')) {
                    return;
                }

                // Ignore if clicking the exact current page
                if (normalizePath(urlObj.pathname) === normalizePath(window.location.pathname) && urlObj.search === window.location.search) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();
                navigateTo(urlObj.pathname + urlObj.search, true);
            });

            // Handle browser Back / Forward buttons
            window.addEventListener('popstate', function(e) {
                const targetUrl = window.location.pathname + window.location.search;
                if (isPortalRoute(targetUrl)) {
                    navigateTo(targetUrl, false);
                }
            });

            window.addEventListener('pageshow', function() {
                hideLoader();
            });
        });

        function markNotificationRead(id, event) {
            fetch(`{{ url('/portal/notifikasi') }}/${id}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).catch(console.error);
        }

        function markAllNotificationsRead(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            fetch(`{{ route('portal.announcements.read_all') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('portalNotifBadge');
                if (badge) badge.remove();

                const sidebarBadge = document.querySelector('.portal-sidebar-badge');
                if (sidebarBadge) sidebarBadge.remove();

                const btnMark = document.getElementById('btnMarkAllRead');
                if (btnMark) btnMark.remove();

                document.querySelectorAll('.portal-notif-item.is-unread').forEach(el => {
                    el.classList.remove('is-unread');
                });
                document.querySelectorAll('.portal-unread-dot').forEach(el => el.remove());

                document.querySelectorAll('.unread-card').forEach(el => {
                    el.classList.remove('unread-card');
                });
                document.querySelectorAll('.feed-unread-pill').forEach(el => el.remove());
                document.querySelectorAll('.feed-card-status').forEach(el => {
                    el.innerHTML = '<span class="text-muted text-sm"><i class="fas fa-check-double text-success"></i> Sudah dibaca</span>';
                });
            })
            .catch(console.error);
        }

        function openPortalLogoutModal() {
            const modal = document.getElementById('portalLogoutModal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closePortalLogoutModal(event) {
            if (event && event.target && event.target.id !== 'portalLogoutModal') {
                return;
            }
            const modal = document.getElementById('portalLogoutModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePortalLogoutModal();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
