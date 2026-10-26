@extends('layouts.master')

@section('title', 'Inicio')

@push('styles')
<style>
/* ============================================================
   INICIO - TABLERO PERSONAL
   Estilo de referencia: tarjetas blancas con borde fino, banda de
   color arriba, icono circular sobre la banda y titulos centrados.
   Prefijo .db- para no colisionar con el resto del sistema.
   Regla: esta vista no muestra montos. Los catalogos financieros
   solo aparecen si el controlador envia $puedeVerFinanzas = true.
   ============================================================ */
.db {
    --db-ink: var(--ink, var(--ink));
    --db-muted: var(--muted, var(--muted));
    --db-line: var(--line, var(--line));
    --db-navy: var(--primary, var(--primary));
    --db-blue: var(--primary-light);
    --db-teal: var(--accent, var(--accent));
    --db-red: var(--coral);
    --db-yellow: var(--sun);
    --db-green: var(--success);
    --db-sky: var(--lilac);
    --db-track: var(--primary-soft, var(--primary-soft));

    color: var(--db-ink);
    font-size: 0.95rem;
    line-height: 1.45;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.db *:focus-visible {
    outline: 3px solid var(--db-blue);
    outline-offset: 3px;
}

.db h1,
.db h2,
.db h3,
.db p {
    text-transform: none;
    letter-spacing: normal;
}

/* ---------- Encabezado ---------- */
.db-accents {
    display: flex;
    gap: 6px;
}

.db-accents span {
    display: block;
    width: 64px;
    height: 4px;
    border-radius: 2px;
}

.db-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.75rem 1.5rem;
}

.db-title {
    font-size: clamp(1.5rem, 2.6vw, 2rem);
    font-weight: 800;
    color: var(--db-ink);
    margin: 0.5rem 0 0;
}

.db-sub {
    margin: 0.3rem 0 0;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--db-muted);
}

.db-date {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--db-navy);
    white-space: nowrap;
}

/* ---------- Tarjeta base ---------- */
.db-card {
    background: #fff;
    border: 1px solid var(--db-line);
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(20, 30, 60, 0.06);
    padding: 1rem 1.15rem 1.15rem;
    min-width: 0;
}

.db-card-title {
    text-align: center;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--db-ink);
    margin: 0 0 0.9rem;
    padding-bottom: 0.7rem;
    border-bottom: 1px solid var(--db-line);
}

.db-card-title small {
    display: block;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--db-muted);
    margin-top: 0.2rem;
}

.db-empty {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--db-muted);
    font-weight: 600;
    font-size: 0.9rem;
}

.db-empty i {
    display: block;
    font-size: 1.8rem;
    color: var(--line-strong);
    margin-bottom: 0.6rem;
}

/* ---------- KPIs (fila superior) ---------- */
.db-kpis {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 1rem;
}

.db-kpi {
    padding: 0;
    text-align: center;
    overflow: visible;
}

.db-kpi-band {
    height: 44px;
    border-radius: 8px 8px 0 0;
    background: var(--c);
}

.db-kpi-icon {
    width: 66px;
    height: 66px;
    margin: -33px auto 0;
    border-radius: 50%;
    background: #fff;
    border: 2px solid var(--c);
    color: var(--c);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    position: relative;
}

.db-kpi-label {
    margin-top: 0.7rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--db-ink);
}

.db-kpi-value {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.1;
    margin: 0.2rem 0 1.1rem;
    font-variant-numeric: tabular-nums;
}

.db-kpi-value.is-text {
    font-size: 1.05rem;
    margin-bottom: 1.2rem;
}

/* ---------- Cuadrículas principales ---------- */
.db-main {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr) minmax(0, 1fr);
    gap: 1rem;
    align-items: start;
}

.db-col-side {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.db-bottom {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr);
    gap: 1rem;
    align-items: start;
}

/* ---------- Barras de progreso ---------- */
.db-bars {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.db-bar {
    display: grid;
    grid-template-columns: 96px minmax(0, 1fr) 46px;
    align-items: center;
    gap: 0.7rem;
    font-weight: 700;
    font-size: 0.9rem;
}

.db-bar-track {
    height: 14px;
    background: var(--db-track);
    border-radius: 3px;
    overflow: hidden;
}

.db-bar-fill {
    height: 100%;
    width: var(--w);
    background: var(--db-navy);
    border-radius: 3px;
    transition: width 0.4s ease;
}

.db-bar-value {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

/* ---------- Gráficas ---------- */
.db-chart {
    position: relative;
    height: 230px;
}

.db-chart-donut {
    height: 210px;
}

/* ---------- Ficha de perfil ---------- */
.db-profile {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.db-avatar {
    width: 92px;
    height: 92px;
    border-radius: 50%;
    background: var(--db-navy);
    color: #fff;
    font-size: 2rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid var(--surface);
    margin-bottom: 0.8rem;
}

.db-name {
    font-size: 1.15rem;
    font-weight: 800;
    margin: 0;
}

.db-role {
    display: inline-block;
    margin-top: 0.4rem;
    padding: 0.2rem 0.7rem;
    border-radius: 999px;
    background: var(--accent-soft);
    color: var(--accent-ink);
    font-size: 0.78rem;
    font-weight: 700;
}

.db-profile-meta {
    width: 100%;
    margin: 1rem 0 0;
    padding-top: 0.9rem;
    border-top: 1px solid var(--db-line);
    display: grid;
    gap: 0.55rem;
    text-align: left;
}

.db-profile-meta div {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 0.88rem;
}

.db-profile-meta dt {
    color: var(--db-muted);
    font-weight: 600;
}

.db-profile-meta dd {
    margin: 0;
    font-weight: 700;
    text-align: right;
}

/* ---------- Actividad reciente ---------- */
.db-feed {
    list-style: none;
    margin: 0;
    padding: 0 0.25rem 0 0;
    max-height: 430px;
    overflow-y: auto;
}

.db-feed li {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 0.75rem;
    padding: 0.65rem 0;
    border-bottom: 1px solid var(--db-line);
}

.db-feed li:last-child {
    border-bottom: none;
}

    .db-feed-ico {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--c);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    /* Chips de acento claro (--sun/--accent): el blanco no se lee, se usa tinta */
    .db-feed-ico[style*="--sun"],
    .db-feed-ico[style*="--accent"] {
        color: var(--ink);
    }

.db-feed-title {
    margin: 0;
    font-weight: 700;
    font-size: 0.9rem;
}

.db-feed-detail {
    margin: 0.1rem 0 0;
    font-size: 0.8rem;
    color: var(--db-muted);
    overflow-wrap: anywhere;
}

.db-feed-time {
    margin: 0.2rem 0 0;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--db-blue);
    font-variant-numeric: tabular-nums;
}

/* ---------- Catálogos ---------- */
.db-catalog-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.db-catalog-bar p {
    margin: 0.2rem 0 0;
    color: var(--db-muted);
    font-size: 0.9rem;
    font-weight: 500;
}

.db-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.1rem;
    border-radius: 6px;
    border: 1px solid var(--db-navy);
    background: var(--db-navy);
    color: #fff;
    font: inherit;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.db-btn:hover {
    background: var(--primary-light);
}

.db-btn-ghost {
    background: #fff;
    color: var(--db-navy);
}

.db-btn-ghost:hover {
    background: var(--surface);
}

.db-btn-sm {
    padding: 0.4rem 0.75rem;
    font-size: 0.8rem;
}

.db-results {
    margin-top: 1.25rem;
}

.db-results[hidden] {
    display: none;
}

.db-results-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    margin-bottom: 0.9rem;
}

.db-results-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.db-results-title i {
    color: var(--db-blue);
}

.db-table-wrap {
    overflow-x: auto;
    border: 1px solid var(--db-line);
    border-radius: 6px;
}

.db-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    min-width: 560px;
}

.db-table th {
    background: var(--surface);
    color: var(--db-navy);
    font-size: 0.78rem;
    font-weight: 800;
    padding: 0.7rem 0.85rem;
    text-align: left;
    white-space: nowrap;
    border-bottom: 1px solid var(--db-line);
}

.db-table td {
    padding: 0.65rem 0.85rem;
    border-top: 1px solid var(--db-line);
    font-weight: 600;
    vertical-align: middle;
}

.db-table tbody tr:first-child td {
    border-top: none;
}

.db-table tbody tr:hover td {
    background: var(--surface);
}

.db-table .num,
.db-table th.num {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.db-table .muted {
    color: var(--muted);
}

.db-badge {
    display: inline-block;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
}

.db-badge-ok {
    background: var(--mint);
    color: var(--accent-ink);
}

.db-badge-off {
    background: var(--line);
    color: var(--muted);
}

.db-state {
    text-align: center;
    padding: 1.8rem 1rem;
    font-weight: 700;
    color: var(--db-muted);
}

.db-state-error {
    color: var(--soft-danger);
}

.db-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    margin-right: 0.5rem;
    vertical-align: -3px;
    border: 2px solid var(--db-track);
    border-top-color: var(--db-blue);
    border-radius: 50%;
    animation: db-spin 0.8s linear infinite;
}

@keyframes db-spin {
    to { transform: rotate(360deg); }
}

/* ---------- Diálogo de catálogos ---------- */
.db-dialog {
    width: min(540px, calc(100vw - 2rem));
    padding: 0;
    border: none;
    border-radius: 10px;
    background: #fff;
    color: var(--db-ink);
    box-shadow: 0 20px 60px rgba(10, 20, 50, 0.3);
}

.db-dialog::backdrop {
    background: rgba(15, 25, 50, 0.45);
}

.db-dialog-inner {
    padding: 1.4rem 1.5rem 1.6rem;
}

.db-dialog-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.db-dialog-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
}

.db-dialog-sub {
    margin: 0.3rem 0 1.1rem;
    font-size: 0.88rem;
    color: var(--db-muted);
    font-weight: 500;
}

.db-dialog-close {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    border-radius: 50%;
    border: 1px solid var(--db-line);
    background: #fff;
    color: var(--db-ink);
    font-size: 1.3rem;
    line-height: 1;
    cursor: pointer;
}

.db-dialog-close:hover {
    background: var(--surface);
}

.db-dialog-options {
    display: grid;
    gap: 0.5rem;
}

.db-opt {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    width: 100%;
    padding: 0.75rem 0.9rem;
    border: 1px solid var(--db-line);
    border-radius: 8px;
    background: #fff;
    color: var(--db-ink);
    text-align: left;
    font: inherit;
    cursor: pointer;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.db-opt:hover {
    border-color: var(--db-blue);
    background: var(--surface);
}

.db-opt-ico {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--db-navy);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}

.db-opt-name {
    display: block;
    font-weight: 800;
    font-size: 0.92rem;
}

.db-opt-desc {
    display: block;
    font-size: 0.78rem;
    color: var(--db-muted);
    font-weight: 500;
}

/* ---------- Responsive ---------- */
@media (max-width: 1180px) {
    .db-kpis {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .db-main {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }

    .db-main .db-col-side {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }

    .db-bottom {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }
}

@media (max-width: 720px) {
    .db-kpis,
    .db-main,
    .db-main .db-col-side,
    .db-bottom {
        grid-template-columns: minmax(0, 1fr);
    }

    .db-card {
        padding: 0.9rem;
    }

    .db-kpi-value {
        font-size: 1.7rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .db *,
    .db-dialog * {
        animation: none !important;
        transition: none !important;
    }
}
</style>
@endpush

@section('content')
@php
    $usuario          = auth()->user();
    $nombreCompleto   = trim((string) ($usuario->name ?? ''));
    $primerNombre     = $nombreCompleto !== '' ? explode(' ', $nombreCompleto)[0] : '';
    $iniciales        = collect(preg_split('/\s+/', $nombreCompleto, -1, PREG_SPLIT_NO_EMPTY))
                          ->take(2)
                          ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
                          ->implode('');

    $hora    = now()->hour;
    $saludo  = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

    $rolEtiqueta      = isset($rol) && $rol ? $rol->label() : null;
    $puedeVerFinanzas = (bool) ($puedeVerFinanzas ?? false);
    $resumen          = array_merge(['fletes' => 0, 'gastos' => 0, 'almacen' => 0, 'total' => 0], $resumenSemana ?? []);
    $acciones         = $accionesRecientes ?? [];
    $ultimoAcceso     = $ultimoAcceso ?? null;
    $fechaUltimo      = !empty($ultimoAcceso?->fecha_evento)
                          ? \Illuminate\Support\Carbon::parse($ultimoAcceso->fecha_evento)->format('d/m H:i')
                          : null;

    // Modulo => [etiqueta, icono, color]
    $modulos = [
        'auth'          => ['Acceso', 'fa-key', 'var(--primary)'],
        'facturacion'   => ['Fletes', 'fa-file-invoice-dollar', 'var(--success)'],
        'gastos'        => ['Gastos', 'fa-receipt', 'var(--coral)'],
        'almacen'       => ['Almacén', 'fa-boxes-stacked', 'var(--primary-light)'],
        'inventario'    => ['Almacén', 'fa-boxes-stacked', 'var(--primary-light)'],
        'configuracion' => ['Configuración', 'fa-sliders', 'var(--sun)'],
    ];
    $otroModulo = ['Otros', 'fa-pen', 'var(--muted)'];

    // Conteo por modulo (sobre las acciones recientes que llegan del controlador)
    $conteoModulo = [];
    foreach ($acciones as $evento) {
        $etiqueta = ($modulos[$evento->modulo] ?? $otroModulo)[0];
        $conteoModulo[$etiqueta] = ($conteoModulo[$etiqueta] ?? 0) + 1;
    }
    arsort($conteoModulo);

    // Conteo por dia de los ultimos 7 dias (sobre las acciones recientes)
    $porDia = [];
    for ($d = 6; $d >= 0; $d--) {
        $porDia[now()->subDays($d)->format('d/m')] = 0;
    }
    foreach ($acciones as $evento) {
        $clave = \Illuminate\Support\Carbon::parse($evento->fecha_evento)->format('d/m');
        if (array_key_exists($clave, $porDia)) {
            $porDia[$clave]++;
        }
    }

    // Distribucion de la semana (solo categorias con valor)
    $distribucion = array_filter([
        'Fletes'  => (int) $resumen['fletes'],
        'Gastos'  => (int) $resumen['gastos'],
        'Almacén' => (int) $resumen['almacen'],
    ], fn ($v) => $v > 0);

    $totalSemana = max((int) $resumen['total'], 1);
    $pct = fn ($v) => min(100, (int) round(((int) $v) / $totalSemana * 100));

    $datosGraficos = [
        'modulos'      => ['labels' => array_keys($conteoModulo), 'values' => array_values($conteoModulo)],
        'dias'         => ['labels' => array_keys($porDia), 'values' => array_values($porDia)],
        'distribucion' => ['labels' => array_keys($distribucion), 'values' => array_values($distribucion)],
    ];

    // Endpoints de catalogos. Los financieros solo si el rol puede verlos.
    $endpoints = [
        'personal'       => url('api/personal'),
        'grupos'         => url('api/grupos'),
        'items'          => url('api/items'),
        'tramos'         => url('api/tramos'),
        'clasificadores' => url('api/clasificadores'),
    ];
    if ($puedeVerFinanzas) {
        $endpoints['bancos'] = url('api/bancos');
        $endpoints['config'] = url('api/config');
    }
@endphp

<div class="main-container w-full">
    <div class="db">

        {{-- ========================================================
             ENCABEZADO
             ======================================================== --}}
        <header>
            <div class="db-accents" aria-hidden="true">
                <span style="background:var(--accent);"></span>
                <span style="background: var(--primary);"></span>
                <span style="background:var(--success);"></span>
            </div>

            <div class="db-header">
                <div>
                    <h1 class="db-title">{{ $saludo }}{{ $primerNombre ? ', ' . $primerNombre : '' }}</h1>
                    <p class="db-sub">Resumen de tu actividad de los últimos 7 días.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="db-date"><i class="fas fa-calendar-day"></i> {{ now()->format('d/m/Y') }}</span>
                </div>
            </div>
        </header>

        @php
            $op = array_merge(
                ['flota' => ['activas' => 0, 'mantenimiento' => 0, 'vendidas' => 0], 'personalActivo' => 0, 'stockBajo' => 0],
                $operacion ?? [],
            );
            $venc = array_merge(
                ['cobros' => collect(), 'productos' => collect()],
                $vencimientos ?? [],
            );
        @endphp

        {{-- ========================================================
             KPIs
             ======================================================== --}}
        <section class="db-kpis" aria-label="Indicadores de la semana">
            <article class="db-card db-kpi" style="--c:var(--accent);">
                <div class="db-kpi-band"></div>
                <div class="db-kpi-icon"><i class="fas fa-layer-group"></i></div>
                <div class="db-kpi-label">Acciones totales</div>
                <div class="db-kpi-value">{{ $resumen['total'] }}</div>
            </article>

            <article class="db-card db-kpi" style="--c:var(--coral);">
                <div class="db-kpi-band"></div>
                <div class="db-kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="db-kpi-label">Fletes registrados</div>
                <div class="db-kpi-value">{{ $resumen['fletes'] }}</div>
            </article>

            <article class="db-card db-kpi" style="--c:var(--sun);">
                <div class="db-kpi-band"></div>
                <div class="db-kpi-icon"><i class="fas fa-receipt"></i></div>
                <div class="db-kpi-label">Gastos registrados</div>
                <div class="db-kpi-value">{{ $resumen['gastos'] }}</div>
            </article>

            <article class="db-card db-kpi" style="--c:var(--primary-light);">
                <div class="db-kpi-band"></div>
                <div class="db-kpi-icon"><i class="fas fa-warehouse"></i></div>
                <div class="db-kpi-label">Movimientos de almacén</div>
                <div class="db-kpi-value">{{ $resumen['almacen'] }}</div>
            </article>

            <article class="db-card db-kpi" style="--c:var(--success);">
                <div class="db-kpi-band"></div>
                <div class="db-kpi-icon"><i class="fas fa-clock"></i></div>
                <div class="db-kpi-label">Último acceso</div>
                <div class="db-kpi-value is-text">{{ $fechaUltimo ?? 'Sin registros' }}</div>
            </article>
        </section>

        {{-- ========================================================
             OPERACIÓN (solo conteos, sin montos)
             ======================================================== --}}
        <section class="db-kpis" aria-label="Estado operativo de la empresa">
            <article class="db-card db-kpi" style="--c:var(--success);">
                <div class="db-kpi-band"></div>
                <div class="db-kpi-icon"><i class="fas fa-truck"></i></div>
                <div class="db-kpi-label">Unidades activas</div>
                <div class="db-kpi-value">{{ $op['flota']['activas'] }}</div>
            </article>

            <article class="db-card db-kpi" style="--c:var(--sun);">
                <div class="db-kpi-band"></div>
                <div class="db-kpi-icon"><i class="fas fa-screwdriver-wrench"></i></div>
                <div class="db-kpi-label">En mantenimiento</div>
                <div class="db-kpi-value">{{ $op['flota']['mantenimiento'] }}</div>
            </article>

            <article class="db-card db-kpi" style="--c:var(--muted);">
                <div class="db-kpi-band"></div>
                <div class="db-kpi-icon"><i class="fas fa-tag"></i></div>
                <div class="db-kpi-label">Vendidas</div>
                <div class="db-kpi-value">{{ $op['flota']['vendidas'] }}</div>
            </article>

            <article class="db-card db-kpi" style="--c:var(--primary-light);">
                <div class="db-kpi-band"></div>
                <div class="db-kpi-icon"><i class="fas fa-users"></i></div>
                <div class="db-kpi-label">Personal activo</div>
                <div class="db-kpi-value">{{ $op['personalActivo'] }}</div>
            </article>

            <article class="db-card db-kpi" style="--c: {{ $op['stockBajo'] > 0 ? 'var(--coral)' : 'var(--success)' }};">
                <div class="db-kpi-band"></div>
                <div class="db-kpi-icon"><i class="fas fa-box-open"></i></div>
                <div class="db-kpi-label">Stock bajo / mínimo</div>
                <div class="db-kpi-value">{{ $op['stockBajo'] }}</div>
            </article>
        </section>

        {{-- ========================================================
             VENCIMIENTOS (solo fechas y conceptos, sin montos)
             ======================================================== --}}
        <section class="row g-3" aria-label="Vencimientos próximos">
            <article class="db-card col-md-6" aria-labelledby="ven-cobros">
                <h2 class="db-card-title" id="ven-cobros">
                    Cobros a crédito por vencer
                    <small>Próximos 7 días (solo fecha de pago)</small>
                </h2>
                @if($venc['cobros']->isEmpty())
                    <div class="db-empty"><i class="fas fa-calendar-check"></i>No hay cobros a crédito que venzan esta semana.</div>
                @else
                    <div class="db-table-wrap">
                        <table class="db-table">
                            <thead>
                                <tr><th>Fecha</th><th>Concepto</th><th>Cliente</th></tr>
                            </thead>
                            <tbody>
                                @foreach($venc['cobros'] as $c)
                                    <tr>
                                        <td class="font-bold">{{ \Illuminate\Support\Carbon::parse($c->fecha_vencimiento)->format('d/m/Y') }}</td>
                                        <td class="text-start">{{ $c->concepto }}</td>
                                        <td class="text-start">{{ $c->cliente_nombre ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>

            <article class="db-card col-md-6" aria-labelledby="ven-productos">
                <h2 class="db-card-title" id="ven-productos">
                    Inventario venciendo
                    <small>Próximos 30 días</small>
                </h2>
                @if($venc['productos']->isEmpty())
                    <div class="db-empty"><i class="fas fa-hourglass-half"></i>Nada del inventario vence en 30 días.</div>
                @else
                    <div class="db-table-wrap">
                        <table class="db-table">
                            <thead>
                                <tr><th>Fecha</th><th>Producto</th><th class="num">Stock</th></tr>
                            </thead>
                            <tbody>
                                @foreach($venc['productos'] as $p)
                                    <tr>
                                        <td class="font-bold">{{ \Illuminate\Support\Carbon::parse($p->fecha_vencimiento)->format('d/m/Y') }}</td>
                                        <td class="text-start">{{ $p->nombre_producto }}</td>
                                        <td class="num">{{ $p->stock_actual }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>
        </section>

        {{-- ========================================================
             GRAFICAS PRINCIPALES
             ======================================================== --}}
        <section class="db-main" aria-label="Gráficas de actividad">

            {{-- Acciones por modulo --}}
            <article class="db-card">
                <h2 class="db-card-title">
                    Acciones por módulo
                    <small>Sobre tus últimas acciones registradas</small>
                </h2>
                @if(count($conteoModulo) === 0)
                    <div class="db-empty"><i class="fas fa-chart-bar"></i>Aún no hay acciones para graficar.</div>
                @else
                    <div class="db-chart">
                        <canvas id="chartModulos" role="img" aria-label="Acciones por módulo"></canvas>
                    </div>
                @endif
            </article>

            {{-- Acciones por dia --}}
            <article class="db-card">
                <h2 class="db-card-title">
                    Actividad diaria
                    <small>Últimos 7 días</small>
                </h2>
                @if(array_sum($porDia) === 0)
                    <div class="db-empty"><i class="fas fa-chart-column"></i>Sin actividad en los últimos 7 días.</div>
                @else
                    <div class="db-chart">
                        <canvas id="chartDias" role="img" aria-label="Actividad diaria de los últimos 7 días"></canvas>
                    </div>
                @endif
            </article>

            {{-- Columna lateral: distribucion + proporciones --}}
            <div class="db-col-side">
                <article class="db-card">
                    <h2 class="db-card-title">
                        Distribución de la semana
                        <small>Fletes, gastos y almacén</small>
                    </h2>
                    @if(count($distribucion) === 0)
                        <div class="db-empty"><i class="fas fa-chart-pie"></i>Sin movimientos esta semana.</div>
                    @else
                        <div class="db-chart db-chart-donut">
                            <canvas id="chartDistribucion" role="img" aria-label="Distribución de la actividad semanal"></canvas>
                        </div>
                    @endif
                </article>

                <article class="db-card">
                    <h2 class="db-card-title">
                        Proporción de tu actividad
                        <small>Porcentaje sobre el total semanal</small>
                    </h2>
                    <div class="db-bars">
                        <div class="db-bar">
                            <span>Fletes</span>
                            <div class="db-bar-track"><div class="db-bar-fill" style="--w: {{ $pct($resumen['fletes']) }}%; background:var(--coral);"></div></div>
                            <span class="db-bar-value">{{ $pct($resumen['fletes']) }}%</span>
                        </div>
                        <div class="db-bar">
                            <span>Gastos</span>
                            <div class="db-bar-track"><div class="db-bar-fill" style="--w: {{ $pct($resumen['gastos']) }}%; background:var(--sun);"></div></div>
                            <span class="db-bar-value">{{ $pct($resumen['gastos']) }}%</span>
                        </div>
                        <div class="db-bar">
                            <span>Almacén</span>
                            <div class="db-bar-track"><div class="db-bar-fill" style="--w: {{ $pct($resumen['almacen']) }}%; background:var(--primary-light);"></div></div>
                            <span class="db-bar-value">{{ $pct($resumen['almacen']) }}%</span>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        {{-- ========================================================
             PERFIL  |  ACCESOS  |  ACTIVIDAD
             ======================================================== --}}
        <section class="db-bottom" aria-label="Perfil y actividad reciente">

            <article class="db-card db-profile" aria-labelledby="perfil-titulo">
                <div class="db-avatar" aria-hidden="true">{{ $iniciales ?: '?' }}</div>
                <h2 class="db-name" id="perfil-titulo">{{ $nombreCompleto }}</h2>
                @if($rolEtiqueta)
                    <span class="db-role">{{ $rolEtiqueta }}</span>
                @endif

                <dl class="db-profile-meta">
                    <div>
                        <dt>Usuario</dt>
                        <dd>{{ $usuario->usuario ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Último acceso</dt>
                        <dd>{{ $fechaUltimo ?? 'Sin registros' }}</dd>
                    </div>
                    <div>
                        <dt>Cuenta desde</dt>
                        <dd>{{ \Illuminate\Support\Carbon::parse($usuario->created_at ?? now())->format('m/Y') }}</dd>
                    </div>
                </dl>
            </article>

            <article class="db-card" aria-labelledby="actividad-titulo">
                <h2 class="db-card-title" id="actividad-titulo">
                    Actividad reciente
                    <small>Tus últimas acciones</small>
                </h2>

                @if(count($acciones) === 0)
                    <div class="db-empty"><i class="fas fa-inbox"></i>Todavía no hay acciones registradas.</div>
                @else
                    <ul class="db-feed">
                        @foreach($acciones as $evento)
                            @php
                                [$etiquetaMod, $iconoMod, $colorMod] = $modulos[$evento->modulo] ?? $otroModulo;
                                $titulo = ucfirst(strtolower(str_replace('_', ' ', (string) $evento->accion)));
                                $fechaEvento = \Illuminate\Support\Carbon::parse($evento->fecha_evento);
                            @endphp
                            <li>
                                <span class="db-feed-ico" style="--c: {{ $colorMod }};" aria-hidden="true">
                                    <i class="fas {{ $iconoMod }}"></i>
                                </span>
                                <div>
                                    <p class="db-feed-title">{{ $titulo }}</p>
                                    @if(!empty($evento->descripcion))
                                        <p class="db-feed-detail">{{ $evento->descripcion }}</p>
                                    @endif
                                    <p class="db-feed-time">
                                        <time datetime="{{ $fechaEvento->toIso8601String() }}">{{ $fechaEvento->format('d/m/Y H:i') }}</time>
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </section>
    </div>
</div>

{{-- Panel de resultados del catalogo consultado --}}
<aside id="nomenPanel" class="db-card db-results" hidden aria-live="polite">
    <div class="db-results-head">
        <h3 class="db-results-title" id="nomenTitle">Catálogo</h3>
        <button type="button" class="db-btn db-btn-ghost db-btn-sm" data-panel-close>
            <i class="fas fa-xmark"></i> Cerrar
        </button>
    </div>
    <div class="db-table-wrap">
        <table class="db-table">
            <thead id="nomenHead"></thead>
            <tbody id="nomenBody"></tbody>
        </table>
    </div>
</aside>

{{-- Dialogo nativo: foco atrapado, Escape para cerrar, lectura por lectores de pantalla --}}
<dialog id="nomenDialog" class="db-dialog" aria-labelledby="nomenDialogTitle">
    <div class="db-dialog-inner">
        <div class="db-dialog-head">
            <h2 class="db-dialog-title" id="nomenDialogTitle">Catálogos</h2>
            <button type="button" class="db-dialog-close" data-close aria-label="Cerrar">&times;</button>
        </div>
        <p class="db-dialog-sub">Elige el catálogo que quieres consultar.</p>

        <div class="db-dialog-options">
            <button type="button" class="db-opt" data-tabla="personal">
                <span class="db-opt-ico"><i class="fas fa-users"></i></span>
                <span><span class="db-opt-name">Personal</span><span class="db-opt-desc">Personas registradas en el sistema</span></span>
            </button>
            <button type="button" class="db-opt" data-tabla="grupos">
                <span class="db-opt-ico"><i class="fas fa-layer-group"></i></span>
                <span><span class="db-opt-name">Grupos</span><span class="db-opt-desc">Agrupación de productos</span></span>
            </button>
            <button type="button" class="db-opt" data-tabla="items">
                <span class="db-opt-ico"><i class="fas fa-box"></i></span>
                <span><span class="db-opt-name">Ítems</span><span class="db-opt-desc">Productos y materiales</span></span>
            </button>
            <button type="button" class="db-opt" data-tabla="tramos">
                <span class="db-opt-ico"><i class="fas fa-route"></i></span>
                <span><span class="db-opt-name">Rutas</span><span class="db-opt-desc">Tramos y distancias</span></span>
            </button>
            <button type="button" class="db-opt" data-tabla="clasificadores">
                <span class="db-opt-ico"><i class="fas fa-receipt"></i></span>
                <span><span class="db-opt-name">Clasificador de gastos</span><span class="db-opt-desc">Tipos de gasto y su alcance</span></span>
            </button>

            @if($puedeVerFinanzas)
                <button type="button" class="db-opt" data-tabla="bancos">
                    <span class="db-opt-ico"><i class="fas fa-building-columns"></i></span>
                    <span><span class="db-opt-name">Bancos</span><span class="db-opt-desc">Cuentas bancarias y saldos</span></span>
                </button>
                <button type="button" class="db-opt" data-tabla="config">
                    <span class="db-opt-ico"><i class="fas fa-sliders"></i></span>
                    <span><span class="db-opt-name">Configuración</span><span class="db-opt-desc">Parámetros financieros del sistema</span></span>
                </button>
            @endif
        </div>
    </div>
</dialog>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const ENDPOINTS = @json($endpoints);
    const FINANZAS  = @json($puedeVerFinanzas);
    const GRAFICOS  = @json($datosGraficos);

    // Paleta de graficas: leida de las variables del spec via getComputedStyle
    // (Chart.js no acepta 'var(...)'; se resuelven a hex una vez).
    const ROOT_CSS = getComputedStyle(document.documentElement);
    const CSSV = n => (ROOT_CSS.getPropertyValue(n) || '').trim() || 'var(--primary)';
    const COLORES = [
        CSSV('--primary'), CSSV('--accent-strong'), CSSV('--coral'), CSSV('--sun'),
        CSSV('--primary-light'), CSSV('--success'), CSSV('--muted')
    ];

    /* ============================================================
       GRAFICAS (Chart.js). Se carga bajo demanda si el layout no lo incluye.
       ============================================================ */
    function cargarChartJs() {
        if (typeof Chart !== 'undefined') return Promise.resolve();
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    function dibujarGraficos() {
        const base = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        };

        const modulos = document.getElementById('chartModulos');
        if (modulos && GRAFICOS.modulos.labels.length) {
            new Chart(modulos, {
                type: 'bar',
                data: {
                    labels: GRAFICOS.modulos.labels,
                    datasets: [{
                        data: GRAFICOS.modulos.values,
                        backgroundColor: GRAFICOS.modulos.labels.map((_, i) => COLORES[i % COLORES.length]),
                        borderRadius: 4,
                        barThickness: 18
                    }]
                },
                options: Object.assign({}, base, {
                    indexAxis: 'y',
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: CSSV('--line') } },
                        y: { grid: { display: false } }
                    }
                })
            });
        }

        const dias = document.getElementById('chartDias');
        if (dias && GRAFICOS.dias.labels.length) {
            new Chart(dias, {
                type: 'bar',
                data: {
                    labels: GRAFICOS.dias.labels,
                    datasets: [{
                        data: GRAFICOS.dias.values,
                        backgroundColor: CSSV('--accent'),
                        borderRadius: 4
                    }]
                },
                options: Object.assign({}, base, {
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: CSSV('--line') } },
                        x: { grid: { display: false } }
                    }
                })
            });
        }

        const dona = document.getElementById('chartDistribucion');
        if (dona && GRAFICOS.distribucion.labels.length) {
            new Chart(dona, {
                type: 'doughnut',
                data: {
                    labels: GRAFICOS.distribucion.labels,
                    datasets: [{
                        data: GRAFICOS.distribucion.values,
                        backgroundColor: [CSSV('--coral'), CSSV('--sun'), CSSV('--primary-light')],
                        borderColor: '#fff',
                        borderWidth: 3
                    }]
                },
                options: Object.assign({}, base, {
                    cutout: '58%',
                    plugins: {
                        legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: { weight: '600' } } }
                    }
                })
            });
        }
    }

    cargarChartJs()
        .then(dibujarGraficos)
        .catch(() => {
            document.querySelectorAll('.db-chart').forEach((el) => {
                el.innerHTML = '<div class="db-empty"><i class="fas fa-triangle-exclamation"></i>No se pudieron cargar las gráficas.</div>';
            });
        });

    /* ============================================================
       CATALOGOS: todo dato del servidor se escapa antes de mostrarse
       ============================================================ */
    const dialog = document.getElementById('nomenDialog');
    const panel  = document.getElementById('nomenPanel');
    const title  = document.getElementById('nomenTitle');
    const head   = document.getElementById('nomenHead');
    const body   = document.getElementById('nomenBody');

    let ultimoCatalogo = null;

    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    const celda = (v) => (v === null || v === undefined || v === '')
        ? '<span class="muted">—</span>'
        : esc(v);

    const dinero = (v) => 'Bs ' + Number(v || 0).toLocaleString('es-BO', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });

    const etiqueta = (texto, activo) =>
        `<span class="db-badge ${activo ? 'db-badge-ok' : 'db-badge-off'}">${esc(texto)}</span>`;

    const siNo = (v) => v ? etiqueta('Sí', true) : '<span class="muted">—</span>';

    const rutasCols  = ['Origen', 'Destino', 'Kilómetros'];
    const rutasRight = [];
    if (FINANZAS) {
        rutasCols.push('Precio total', 'Precio por tonelada');
        rutasRight.push(3, 4);
    }

    const ESQUEMAS = {
        personal: {
            cols: ['Nombres', 'Apellidos', 'CI', 'Teléfono', 'Estado'],
            right: [],
            fila: (r) => [
                celda(r.nombres), celda(r.apellidos), celda(r.ci), celda(r.telefono),
                etiqueta(Number(r.estado) === 1 ? 'Activo' : 'Inactivo', Number(r.estado) === 1)
            ]
        },
        grupos: {
            cols: ['Nombre', 'Descripción', 'Productos'],
            right: [2],
            fila: (r) => [celda(r.nombre), celda(r.descripcion), celda(Number(r.total_productos || 0))]
        },
        items: {
            cols: ['Código', 'Nombre', 'Grupo', 'Unidad', 'Stock mínimo'],
            right: [4],
            fila: (r) => [
                celda(r.codigo), celda(r.nombre_producto), celda(r.categoria),
                celda(r.unidad_medida), celda(Number(r.stock_minimo || 0))
            ]
        },
        tramos: {
            cols: rutasCols,
            right: rutasRight,
            fila: (r) => {
                const base = [celda(r.origen), celda(r.destino), celda(r.kilometros)];
                return FINANZAS
                    ? base.concat([dinero(r.precio_total), dinero(r.precio_dolar_tonelada)])
                    : base;
            }
        },
        clasificadores: {
            cols: ['Código', 'Descripción', 'Tipo de gasto', 'Afecta unidad', 'Afecta general'],
            right: [],
            fila: (r) => [
                celda(r.codigo), celda(r.descripcion), celda(r.tipo_gasto),
                siNo(r.afecta_unidad), siNo(r.afecta_general)
            ]
        }
    };

    if (FINANZAS) {
        ESQUEMAS.bancos = {
            cols: ['Banco', 'Tipo', 'N.º de cuenta', 'Titular', 'Saldo'],
            right: [4],
            fila: (r) => [
                celda(r.nombre_banco), celda(r.tipo_cuenta), celda(r.numero_cuenta),
                celda(r.titular), dinero(r.saldo_actual)
            ]
        };

        const etiquetasConfig = {
            tipo_cambio: 'Tipo de cambio (Bs por USD)',
            precio_tonelada_usd: 'Precio por tonelada (Bs)',
            valor_flota_por_unidad: 'Valor por unidad de flota (Bs)',
            saldo_inicial_proveedores: 'Saldo inicial de proveedores (Bs)',
            fuente_tipo_cambio: 'Fuente del tipo de cambio'
        };

        ESQUEMAS.config = {
            cols: ['Parámetro', 'Valor'],
            right: [1],
            // La respuesta llega como { data: { data: { ... } } }
            filas: (json) => Object.entries((json.data && json.data.data) || {}).map(
                ([clave, valor]) => [esc(etiquetasConfig[clave] || clave), celda(valor)]
            )
        };
    }

    function mostrarEstado(mensaje, clase, extra) {
        head.innerHTML = '';
        body.innerHTML = `<tr><td colspan="6" class="db-state ${clase || ''}">${mensaje}${extra || ''}</td></tr>`;
    }

    function pintarTabla(esquema, filas) {
        const cols = esquema.cols;
        head.innerHTML = '<tr>' + cols.map((c, i) =>
            `<th scope="col"${esquema.right.includes(i) ? ' class="num"' : ''}>${esc(c)}</th>`
        ).join('') + '</tr>';

        if (!filas.length) {
            body.innerHTML = `<tr><td colspan="${cols.length}" class="db-state">Sin registros en este catálogo.</td></tr>`;
            return;
        }

        body.innerHTML = filas.map((fila) =>
            '<tr>' + fila.map((celdaHtml, i) =>
                `<td${esquema.right.includes(i) ? ' class="num"' : ''}>${celdaHtml}</td>`
            ).join('') + '</tr>'
        ).join('');
    }

    async function cargarCatalogo(tabla) {
        const esquema = ESQUEMAS[tabla];
        const url = ENDPOINTS[tabla];
        if (!esquema || !url) return;

        ultimoCatalogo = tabla;
        const nombre = document.querySelector(`[data-tabla="${tabla}"] .db-opt-name`)?.textContent || tabla;
        title.innerHTML = '<i class="fas fa-table"></i> ' + esc(nombre);
        panel.hidden = false;
        panel.setAttribute('aria-busy', 'true');
        mostrarEstado('<span class="db-spinner" aria-hidden="true"></span>Cargando…');

        try {
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const json = await res.json();
            const filas = esquema.filas
                ? esquema.filas(json)
                : (json.data || []).map(esquema.fila);

            pintarTabla(esquema, filas);
        } catch (err) {
            mostrarEstado(
                'No se pudo cargar el catálogo.',
                'db-state-error',
                '<br><button type="button" class="db-btn db-btn-ghost db-btn-sm" data-retry style="margin-top:.7rem;"><i class="fas fa-rotate"></i> Reintentar</button>'
            );
        } finally {
            panel.removeAttribute('aria-busy');
        }
    }

    function abrirDialogo() {
        if (typeof dialog.showModal === 'function') dialog.showModal();
    }

    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close();
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-open-catalogos]')) return abrirDialogo();

        const opcion = e.target.closest('[data-tabla]');
        if (opcion && dialog.contains(opcion)) {
            dialog.close();
            return cargarCatalogo(opcion.dataset.tabla);
        }

        if (e.target.closest('[data-close]')) return dialog.close();

        if (e.target.closest('[data-panel-close]')) {
            panel.hidden = true;
            return;
        }

        if (e.target.closest('[data-retry]') && ultimoCatalogo) {
            return cargarCatalogo(ultimoCatalogo);
        }
    });
})();
</script>
@endpush
