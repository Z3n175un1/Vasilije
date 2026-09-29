<!DOCTYPE html>
<html lang="es" class="animate-fade-in">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title') - DS TRANSPORTE S.R.L
    </title>

    {{-- =====================================================
         FUENTES
         ===================================================== --}}

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Uncut+Sans:wght@400;500;700;800&display=swap"
        rel="stylesheet"
    >

    {{-- =====================================================
         FONT AWESOME
         ===================================================== --}}

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        rel="stylesheet"
    >

    {{-- =====================================================
         BOOTSTRAP
         ===================================================== --}}

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    {{-- =====================================================
         VITE
         ===================================================== --}}

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    {{-- =====================================================
         FAVICON
         ===================================================== --}}

    <link
        rel="icon"
        type="image/x-icon"
        href="{{ asset('favicon.ico') }}"
    >

    @stack('styles')
</head>


<body class="animate-fade-in">

    {{-- =====================================================
         COMPONENTES GLOBALES
         ===================================================== --}}

    <x-notification />
    <x-confirm-dialog />
    <x-loading-spinner />


    @auth

    {{-- =====================================================
         HEADER / BOTÓN MENÚ
         ===================================================== --}}

    <header
        class="navbar-bento-floating animate-slide-right"
        id="app-header"
    >

        {{-- =================================================
             BOTÓN HAMBURGUESA
             ================================================= --}}

        <button
            type="button"
            class="btn-bento btn-menu-toggle p-3 font-bold"
            id="menuToggle"
            aria-expanded="false"
            aria-controls="menuDrawer"
            aria-label="Abrir menú"
        >

            <div
                class="hamburger-icon"
                id="hamburgerIcon"
                aria-hidden="true"
            >
                <span class="bar bar-1"></span>
                <span class="bar bar-2"></span>
                <span class="bar bar-3"></span>
            </div>

            <span class="menu-toggle-label">
                MENÚ
            </span>

        </button>


        {{-- =================================================
             BACKDROP
             ================================================= --}}

        <div
            class="menu-backdrop"
            id="menuBackdrop"
        ></div>


        {{-- =================================================
             SIDEBAR
             ================================================= --}}

        <aside
            class="menu-sidebar-drawer"
            id="menuDrawer"
            aria-label="Menú principal"
        >

            {{-- =================================================
                 CONTENIDO DEL DRAWER
                 ================================================= --}}

            <div class="drawer-content-wrapper">

                {{-- =================================================
                     HEADER DEL DRAWER
                     ================================================= --}}

                <div class="drawer-header pb-3 mb-4">

                    <h1 class="text-white font-black mb-0 fs-mid">
                        DS TRANSPORTE S.R.L
                    </h1>

                    <p class="small fw-bold text-white mt-2 mb-0">
                        <i class="fas fa-user me-1"></i>

                        {{ auth()->user()->name }}
                    </p>

                </div>


                {{-- =================================================
                     NAVEGACIÓN
                     ================================================= --}}

                <nav class="drawer-nav-links">

                    {{-- =================================================
                         INICIO
                         ================================================= --}}

                    <a
                        href="{{ route('documentos.index') }}"
                        class="{{ request()->routeIs('documentos*') ? 'active' : '' }}"
                    >
                        <i class="fas fa-home"></i>

                        <span>
                            INICIO
                        </span>
                    </a>


                    {{-- =================================================
                         CATEGORÍAS
                         ================================================= --}}

                    @php

                        $categorias = [

                            'OPERACIONES' => [
                                'icon' => 'fa-truck-fast',

                                'items' => [
                                    [
                                        'route' => 'dashboard.index',
                                        'label' => 'UNIDADES',
                                        'icon' => 'fa-truck',
                                    ],

                                    [
                                        'route' => 'personal.index',
                                        'label' => 'PERSONAL',
                                        'icon' => 'fa-users',
                                    ],

                                    [
                                        'route' => 'almacen.index',
                                        'label' => 'MOV. ALMACÉN',
                                        'icon' => 'fa-warehouse',
                                    ],

                                    [
                                        'route' => 'tramos.index',
                                        'label' => 'RUTAS',
                                        'icon' => 'fa-route',
                                    ],
                                ],
                            ],

                            'FINANCIERO' => [
                                'icon' => 'fa-money-bill-wave',

                                'items' => [
                                    [
                                        'route' => 'facturacion.index',
                                        'label' => 'FACTURACIÓN',
                                        'icon' => 'fa-file-invoice',
                                    ],

                                    [
                                        'route' => 'bancos.index',
                                        'label' => 'BANCOS',
                                        'icon' => 'fa-university',
                                    ],

                                    [
                                        'route' => 'gastos-generales.index',
                                        'label' => 'GASTOS GENERALES',
                                        'icon' => 'fa-file-invoice-dollar',
                                    ],

                                    [
                                        'route' => 'reportes.index',
                                        'label' => 'REPORTES',
                                        'icon' => 'fa-chart-bar',
                                    ],
                                ],
                            ],

                            'INVENTARIO' => [
                                'icon' => 'fa-boxes-stacked',

                                'items' => [
                                    [
                                        'route' => 'almacen.index',
                                        'label' => 'MOV. ALMACÉN',
                                        'icon' => 'fa-warehouse',
                                    ],

                                    [
                                        'route' => 'items.index',
                                        'label' => 'ÍTEMS',
                                        'icon' => 'fa-box',
                                    ],

                                    [
                                        'route' => 'grupos.index',
                                        'label' => 'GRUPOS',
                                        'icon' => 'fa-layer-group',
                                    ],

                                    [
                                        'route' => 'proveedores.index',
                                        'label' => 'PROVEEDORES',
                                        'icon' => 'fa-handshake',
                                    ],
                                ],
                            ],

                        ];

                    @endphp


                    {{-- =================================================
                         RENDER DE CATEGORÍAS
                         ================================================= --}}

                    @foreach($categorias as $nombre => $categoria)

                        <div class="nav-category">

                            {{-- =============================================
                                 BOTÓN DE CATEGORÍA
                                 ============================================= --}}

                            <button
                                type="button"
                                class="category-toggle"
                                aria-expanded="false"
                                aria-controls="category-{{ Str::slug($nombre) }}"
                            >

                                <span>
                                    <i class="fas {{ $categoria['icon'] }}"></i>

                                    {{ $nombre }}
                                </span>

                                <i class="fas fa-chevron-down"></i>

                            </button>


                            {{-- =============================================
                                 ITEMS DE CATEGORÍA
                                 ============================================= --}}

                            <div
                                class="category-items"
                                id="category-{{ Str::slug($nombre) }}"
                            >

                                @foreach($categoria['items'] as $item)

                                    <a
                                        href="{{ route($item['route']) }}"
                                        class="{{ request()->routeIs(explode('.', $item['route'])[0] . '*') ? 'active' : '' }}"
                                    >

                                        <i
                                            class="fas {{ $item['icon'] }}"
                                        ></i>

                                        <span>
                                            {{ $item['label'] }}
                                        </span>

                                    </a>

                                @endforeach

                            </div>

                        </div>

                    @endforeach


                    {{-- =================================================
                         CONFIGURACIÓN - SOLO ADMIN
                         ================================================= --}}

                    @if(auth()->user()?->rol === 'admin')

                        <div class="drawer-admin-section">

                            {{-- =============================================
                                 USUARIOS
                                 ============================================= --}}

                            <a
                                href="{{ route('usuarios.index') }}"
                                class="{{ request()->routeIs('usuarios*') ? 'active' : '' }}"
                            >

                                <i class="fas fa-user-shield"></i>

                                <span>
                                    USUARIOS
                                </span>

                            </a>


                            {{-- =============================================
                                 CONFIGURACIÓN
                                 ============================================= --}}

                            <a
                                href="{{ route('configuracion.index') }}"
                                class="{{ request()->routeIs('configuracion*') ? 'active' : '' }}"
                            >

                                <i class="fas fa-sliders"></i>

                                <span>
                                    CONFIGURACIÓN
                                </span>

                            </a>

                        </div>

                    @endif

                </nav>

            </div>


            {{-- =================================================
                 FOOTER DEL DRAWER
                 ================================================= --}}

            <div class="drawer-footer">

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    id="logoutForm"
                >

                    @csrf

                    <button
                        type="button"
                        class="btn font-bold w-100 py-3"
                        id="logoutBtn"
                    >

                        <i class="fas fa-power-off me-2"></i>

                        CERRAR SESIÓN

                    </button>

                </form>

            </div>

        </aside>

    </header>


    {{-- =====================================================
         LOGO SUPERIOR DERECHO
         ===================================================== --}}

    <div class="top-right-logo">

        <img
            src="{{ asset('favicon.ico') }}"
            alt="DS Transporte"
        >

    </div>

    @endauth


    {{-- =====================================================
         CONTENIDO PRINCIPAL
         ===================================================== --}}

    <main class="animate-fade-in">

        @yield('content')

    </main>


    {{-- =====================================================
         BOOTSTRAP
         ===================================================== --}}

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>


    {{-- =====================================================
         SWEETALERT
         ===================================================== --}}

    <script
        src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
    ></script>


    {{-- =====================================================
         CHART.JS
         ===================================================== --}}

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
    ></script>


    @stack('scripts')

</body>

</html>
