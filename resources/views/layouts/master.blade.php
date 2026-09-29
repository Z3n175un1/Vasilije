<!DOCTYPE html>
<html lang="es" class="animate-fade-in">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - DS TRANSPORTE S.R.L</title>

    {{-- Fuentes --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Uncut+Sans:wght@400;500;700;800&display=swap"
        rel="stylesheet"
    >

    {{-- Font Awesome --}}
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        rel="stylesheet"
    >

    {{-- Bootstrap --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    {{-- Vite: CSS + JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Favicon --}}
    <link
        rel="icon"
        type="image/x-icon"
        href="{{ asset('favicon.ico') }}"
    >

    @stack('styles')
</head>


<body class="animate-fade-in">

    {{-- Componentes globales --}}
    <x-notification />
    <x-confirm-dialog />
    <x-loading-spinner />


    @auth

    {{-- =====================================================
         NAVBAR
         ===================================================== --}}

    <header
        class="navbar-bento-floating animate-slide-right"
        id="app-header"
    >

        {{-- BOTÓN MENÚ --}}

        <button
            type="button"
            class="btn-bento btn-menu-toggle p-3 font-bold"
            id="menuToggle"
            aria-expanded="false"
            aria-controls="menuDrawer"
            style="
                display:flex;
                align-items:center;
                justify-content:center;
                gap:0.75rem;
                border-width:4px !important;
                border-radius:0 !important;
            "
        >

            <div
                class="hamburger-icon"
                id="hamburgerIcon"
            >
                <span class="bar bar-1"></span>
                <span class="bar bar-2"></span>
                <span class="bar bar-3"></span>
            </div>

            <span
                style="
                    font-size:0.85rem;
                    letter-spacing:1px;
                "
            >
                MENÚ
            </span>

        </button>


        {{-- BACKDROP --}}

        <div
            class="menu-backdrop"
            id="menuBackdrop"
        ></div>


        {{-- =================================================
             SIDEBAR
             ================================================= --}}

        <div
            class="menu-sidebar-drawer"
            id="menuDrawer"
        >

            {{-- CONTENIDO --}}

            <div class="drawer-content-wrapper">


                {{-- HEADER DEL DRAWER --}}

                <div class="drawer-header pb-3 mb-4">

                    <h1
                        class="text-white font-black mb-0 fs-mid d-flex flex-wrap align-items-center gap-1"
                    >
                        <span>
                            DS TRANSPORTE S.R.L
                        </span>
                    </h1>

                    <p
                        class="small fw-bold text-white mt-2 mb-0"
                        style="opacity:0.8;"
                    >
                        <i class="fas fa-user me-1"></i>

                        {{ auth()->user()->name }}
                    </p>

                </div>


                {{-- =================================================
                     NAVEGACIÓN
                     ================================================= --}}

                <nav class="drawer-nav-links flex flex-col gap-1">


                    {{-- INICIO --}}

                    <a
                        href="{{ route('documentos.index') }}"
                        class="{{ request()->routeIs('documentos*') ? 'active' : '' }}"
                        style="
                            display:flex;
                            align-items:center;
                            gap:0.75rem;
                            padding:0.75rem 1rem;
                            background:white ;
                            color:white;
                            border:3px solid #000;
                            border-radius:8px;
                            font-weight:900;
                            text-decoration:none;
                        "
                    >
                        <i class="fas fa-home me-2"></i>

                        INICIO
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
                                        'icon' => 'fa-truck'
                                    ],

                                    [
                                        'route' => 'personal.index',
                                        'label' => 'PERSONAL',
                                        'icon' => 'fa-users'
                                    ],

                                    [
                                        'route' => 'almacen.index',
                                        'label' => 'MOV. ALMACÉN',
                                        'icon' => 'fa-warehouse'
                                    ],

                                    [
                                        'route' => 'tramos.index',
                                        'label' => 'RUTAS',
                                        'icon' => 'fa-route'
                                    ],
                                ]
                            ],


                            'FINANCIERO' => [
                                'icon' => 'fa-money-bill-wave',

                                'items' => [
                                    [
                                        'route' => 'facturacion.index',
                                        'label' => 'FACTURACIÓN',
                                        'icon' => 'fa-file-invoice'
                                    ],

                                    [
                                        'route' => 'bancos.index',
                                        'label' => 'BANCOS',
                                        'icon' => 'fa-university'
                                    ],

                                    [
                                        'route' => 'gastos-generales.index',
                                        'label' => 'GASTOS GENERALES',
                                        'icon' => 'fa-file-invoice-dollar'
                                    ],

                                    [
                                        'route' => 'reportes.index',
                                        'label' => 'REPORTES',
                                        'icon' => 'fa-chart-bar'
                                    ],
                                ]
                            ],


                            'INVENTARIO' => [
                                'icon' => 'fa-boxes-stacked',

                                'items' => [
                                    [
                                        'route' => 'almacen.index',
                                        'label' => 'MOV. ALMACÉN',
                                        'icon' => 'fa-warehouse'
                                    ],

                                    [
                                        'route' => 'items.index',
                                        'label' => 'ÍTEMS',
                                        'icon' => 'fa-box'
                                    ],

                                    [
                                        'route' => 'grupos.index',
                                        'label' => 'GRUPOS',
                                        'icon' => 'fa-layer-group'
                                    ],

                                    [
                                        'route' => 'proveedores.index',
                                        'label' => 'PROVEEDORES',
                                        'icon' => 'fa-handshake'
                                    ],
                                ]
                            ],

                        ];

                    @endphp


                    {{-- RECORRER CATEGORÍAS --}}

                    @foreach($categorias as $nombre => $categoria)

                        <div class="nav-category">


                            {{-- BOTÓN CATEGORÍA --}}

                            <button
                                type="button"
                                class="category-toggle flex items-center justify-between py-2 px-3 fw-bold text-uppercase text-sm"
                                aria-expanded="false"
                                style="
                                    background:#fff;
                                    border:2px solid #000;
                                    border-radius:8px;
                                    color:#000;
                                    gap:0.5rem;
                                    width:100%;
                                    text-align:left;
                                    transition:all 0.2s ease;
                                "
                                onmouseover="this.style.background='#000'; this.style.color='#fff'; this.querySelector('i.fa-chevron-down').style.color='#fff'; this.querySelector('i.fas:not(.fa-chevron-down)').style.color='#fff';"
                                onmouseout="this.style.background='#fff'; this.style.color='#000'; this.querySelector('i.fa-chevron-down').style.color='#000'; this.querySelector('i.fas:not(.fa-chevron-down)').style.color='#000';"
>

                                <span class="flex items-center gap-2">

                                    <i
                                        class="fas {{ $categoria['icon'] }}"
                                    ></i>

                                    {{ $nombre }}

                                </span>


                                <i
                                    class="fas fa-chevron-down transition-transform duration-200"
                                ></i>

                            </button>


                            {{-- ITEMS --}}

                            <div
                                class="category-items flex flex-col gap-1 mt-2"
                                style="
                                    overflow:hidden;
                                    max-height:0;
                                    transition:max-height 0.3s ease;
                                "
                            >

                                @foreach($categoria['items'] as $item)

                                    <a
                                        href="{{ route($item['route']) }}"
                                        class="{{ request()->routeIs(explode('.', $item['route'])[0].'*') ? 'active' : '' }}"
                                        style="
                                            display:block;
                                            padding:0.5rem 2rem;
                                            background:#fff;
                                            border:2px solid #000;
                                            border-radius:6px;
                                            font-weight:700;
                                            color:#000;
                                            text-decoration:none;
                                        "
                                    >

                                        <i
                                            class="fas {{ $item['icon'] }} me-2"
                                        ></i>

                                        {{ $item['label'] }}

                                    </a>

                                @endforeach

                            </div>

                        </div>

                    @endforeach


                    {{-- =================================================
                         CONFIGURACIÓN - SOLO ADMIN
                         ================================================= --}}

                    @if(auth()->user()?->rol === 'admin')

                        <div
                            style="
                                margin-top:1rem;
                                border-top:2px solid #000;
                                padding-top:1rem;
                            "
                        >

                            {{-- USUARIOS --}}

                            <a
                                href="{{ route('usuarios.index') }}"
                                class="{{ request()->routeIs('usuarios*') ? 'active' : '' }}"
                                style="
                                    display:flex;
                                    align-items:center;
                                    gap:0.75rem;
                                    padding:0.75rem 1rem;
                                    background:#fff;
                                    border:2px solid #000;
                                    border-radius:8px;
                                    color:black;
                                    font-weight:900;
                                    text-decoration:none;
                                "
                            >

                                <i class="fas fa-user-shield me-2"></i>

                                USUARIOS

                            </a>


                            {{-- CONFIGURACIÓN --}}

                            <a
                                href="{{ route('configuracion.index') }}"
                                class="{{ request()->routeIs('configuracion*') ? 'active' : '' }}"
                                style="
                                    display:flex;
                                    align-items:center;
                                    gap:0.75rem;
                                    padding:0.75rem 1rem;
                                    background:#fff;
                                    border:2px solid #000;
                                    border-radius:8px;
                                    color:#000;
                                    font-weight:900;
                                    text-decoration:none;
                                    margin-top:0.5rem;
                                "
                            >

                                <i class="fas fa-sliders me-2"></i>

                                CONFIGURACIÓN

                            </a>

                        </div>

                    @endif

                </nav>

            </div>


            {{-- =================================================
                 FOOTER
                 ================================================= --}}

            <div class="drawer-footer pt-3">


                {{-- LOGOUT --}}

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
                        style="
                            border:2px solid #000;
                            border-radius:12px;
                            font-size:0.9rem;
                            background:#fff;
                            color:#000;
                        "
                    >

                        <i class="fas fa-power-off me-2"></i>

                        CERRAR SESION

                    </button>

                </form>

            </div>

        </div>

    </header>


    {{-- =====================================================
         LOGO SUPERIOR DERECHO
         ===================================================== --}}

    <div
        style="
            position:fixed;
            top:24px;
            right:24px;
            z-index:2000;
            background:#fff;
            border:2px solid #000;
            border-radius:12px;
            padding:8px;
            display:flex;
            align-items:center;
            justify-content:center;
            width:72px;
            height:72px;
        "
    >

        <img
            src="{{ asset('favicon.ico') }}"
            alt="DS"
            style="
                width:100%;
                height:100%;
                object-fit:contain;
                border-radius:8px;
            "
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
         SCRIPTS EXTERNOS
         ===================================================== --}}

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>

    <script
        src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
    ></script>

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
    ></script>


    @stack('scripts')

</body>

</html>
