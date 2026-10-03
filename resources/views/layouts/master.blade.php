<!DOCTYPE html>
<html lang="es" class="animate-fade-in">
@vite(['resources/css/app.css', 'resources/js/app.js'])
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

    {{-- Debe ir primero: su script inline marca `ds-pending` en el <html>
         antes de que se pinte el primer frame, para que la pagina nueva
         aparezca ya cubierta y no se vea el parpadeo. --}}
    <x-page-transition />

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

                        // El menu se construye a partir de la capacidad real del
                        // usuario, no solo de su rol. Un usuario de solo lectura
                        // no ve los enlaces de escritura porque no puede usarlos:
                        // antes se mostraban todos y el backend los aceptaba.
                        $rolUsuario = \App\Enums\Rol::normalizar(auth()->user()?->rol);

                        // Verifica la capacidad con el formato `accion:modulo`
                        // usado en la matriz de permisos.
                        $puede = function (string $cap) {
                            [$accion, $modulo] = explode(':', $cap);

                            return (bool) auth()->user()?->can($accion, $modulo);
                        };

                        // Solo se pintan los items que el usuario puede usar, y
                        // si una lista se queda vacia se descarta entera para no
                        // mostrar desplegables sin contenido.
                        //
                        // `$puede` se importa con `use` porque la arrow function
                        // vive dentro de este closure y no lo alcanza por
                        // ámbito: sin el `use`, PHP compila `$puede` como
                        // variable indefinida y la vista revienta.
                        $seccion = function (string $icono, array $items) use ($puede): array {
                            $items = array_values(array_filter($items, fn ($i) => $puede($i['cap'])));

                            return ['icon' => $icono, 'items' => $items];
                        };

                        $categorias = [
                            'CATÁLOGOS' => $seccion('fa-book', [
                                ['route' => 'personal.index', 'label' => 'PERSONAL', 'icon' => 'fa-users', 'cap' => 'ver:personal'],
                                ['route' => 'tramos.index', 'label' => 'RUTAS', 'icon' => 'fa-route', 'cap' => 'ver:tramos'],
                                ['route' => 'bancos.index', 'label' => 'BANCOS', 'icon' => 'fa-university', 'cap' => 'ver:bancos'],
                                ['route' => 'grupos.index', 'label' => 'GRUPOS', 'icon' => 'fa-layer-group', 'cap' => 'ver:grupos'],
                                ['route' => 'items.index', 'label' => 'ÍTEMS', 'icon' => 'fa-box', 'cap' => 'ver:items'],
                                ['route' => 'proveedores.index', 'label' => 'PROVEEDORES', 'icon' => 'fa-handshake', 'cap' => 'ver:proveedores'],
                                ['route' => 'almacen.index', 'label' => 'MOV. ALMACÉN', 'icon' => 'fa-warehouse', 'cap' => 'ver:almacen'],
                            ]),

                            'TRANSACCIONES' => $seccion('fa-arrow-right-arrow-left', [
                                ['route' => 'dashboard.index', 'label' => 'UNIDADES', 'icon' => 'fa-truck', 'cap' => 'ver:vehiculos'],
                                ['route' => 'gastos-generales.index', 'label' => 'GASTOS GENERALES', 'icon' => 'fa-file-invoice-dollar', 'cap' => 'ver:gastos_generales'],
                                ['route' => 'clasificadores.index', 'label' => 'CLASIFICADOR DE GASTOS', 'icon' => 'fa-receipt', 'cap' => 'ver:clasificadores'],
                            ]),
                        ];

                        // Ocultar las categorias que se quedaron sin items.
                        $categorias = array_filter($categorias, fn ($c) => !empty($c['items']));

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
                         ATAJOS SOLTOS
                         Facturación y Reportes no son catálogos ni
                         transacciones: van como acceso directo.
                         ================================================= --}}

                    @if($puede('ver:facturacion'))

                        <a
                            href="{{ route('facturacion.index') }}"
                            class="{{ request()->routeIs('facturacion*') ? 'active' : '' }}"
                        >

                            <i class="fas fa-file-invoice-dollar"></i>

                            <span>
                                FACTURACIÓN
                            </span>

                        </a>

                    @endif

                    @if($puede('ver:reportes'))

                        <a
                            href="{{ route('reportes.index') }}"
                            class="{{ request()->routeIs('reportes*') ? 'active' : '' }}"
                        >

                            <i class="fas fa-chart-bar"></i>

                            <span>
                                REPORTES
                            </span>

                        </a>

                    @endif


                    {{-- =================================================
                         CONFIGURACIÓN - SOLO ADMIN
                         ================================================= --}}

                    @if($rolUsuario->esAdmin())

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
