<!DOCTYPE html>
<html lang="es" class="animate-fade-in">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VASILIJE') - Control de Gastos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Uncut+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @production
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @endproduction
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @stack('styles')
</head>
<body class="animate-fade-in">
    <x-notification />
    <x-confirm-dialog />
    <x-loading-spinner />

    @auth
    <header class="navbar-bento-floating animate-slide-right" id="app-header">
        <button class="btn-bento btn-menu-toggle p-3 font-bold"
                id="menuToggle"
                style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; border-width: 4px !important; border-radius: 0 !important;">
            <div class="hamburger-icon" id="hamburgerIcon">
                <span class="bar bar-1"></span>
                <span class="bar bar-2"></span>
                <span class="bar bar-3"></span>
            </div>
            <span style="font-size: 0.85rem; letter-spacing: 1px;">MENÚ</span>
        </button>

        <div class="menu-backdrop" id="menuBackdrop"></div>

        <div class="menu-sidebar-drawer" id="menuDrawer">
            <div class="drawer-content-wrapper">
                <div class="drawer-header border-bottom border-black pb-3 mb-4">
                    <h1 class="text-black font-black mb-0 fs-mid d-flex flex-wrap align-items-center gap-1 animate-pulse">
                        <span>CONTROL</span>
                        <span class="rounded-3 bg-black text-warning px-2 py-1" style="font-size: 0.9rem;">FLOTA</span>
                    </h1>
                </div>

                <nav class="drawer-nav-links d-flex flex-column gap-2" style="transition-delay: calc(0.05s * var(--i, 0));">
                    @php $links = [
                        ['route' => 'documentos.index', 'label' => 'INICIO', 'icon' => 'fa-home'],
                        ['route' => 'dashboard.index', 'label' => 'UNIDADES', 'icon' => 'fa-truck'],
                        ['route' => 'personal.index', 'label' => 'PERSONAL', 'icon' => 'fa-users'],
                        ['route' => 'grupos.index', 'label' => 'GRUPOS', 'icon' => 'fa-layer-group'],
                        ['route' => 'items.index', 'label' => 'ÍTEMS', 'icon' => 'fa-box'],
                        ['route' => 'almacen.index', 'label' => 'ALMACÉN', 'icon' => 'fa-warehouse'],
                        ['route' => 'tramos.index', 'label' => 'RUTAS', 'icon' => 'fa-route'],
                        ['route' => 'facturacion.index', 'label' => 'FACTURACIÓN', 'icon' => 'fa-file-invoice'],
                        ['route' => 'bancos.index', 'label' => 'BANCOS', 'icon' => 'fa-university'],
                        ['route' => 'reportes.index', 'label' => 'REPORTES', 'icon' => 'fa-chart-bar'],
                    ]; @endphp
                    @foreach($links as $link)
                    <a href="{{ route($link['route']) }}"
                       class="{{ request()->routeIs(explode('.', $link['route'])[0].'*') ? 'active' : '' }}"
                       style="--i: {{ $loop->index }};">
                        <i class="fas {{ $link['icon'] }} me-2"></i>
                        {{ $link['label'] }}
                    </a>
                    @endforeach
                </nav>
            </div>

            <div class="drawer-footer border-top border-black pt-3">
                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <button type="button"
                            class="btn-bento btn-bento-primary bg-black text-warning border-black py-2 w-100 font-bold hover-scale"
                            id="logoutBtn"
                            style="border-width: 4px !important; border-radius: 0 !important; font-size: 0.9rem;">
                        <i class="fas fa-power-off me-2"></i> CERRAR SESION
                    </button>
                </form>
            </div>
        </div>
    </header>
    @endauth

    <main class="animate-fade-in">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>