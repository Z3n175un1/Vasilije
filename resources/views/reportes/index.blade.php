@extends('layouts.master')

@section('title', 'Reportes')

@push('styles')
<style>
/* ============================================================
   VARIABLES CSS - SISTEMA BENTO
   ============================================================ */
/* Tokens desde la hoja global (app-vibrant.css) */

/* ============================================================
   FILTROS - SECCIÓN MEJORADA
   ============================================================ */
.filter-section {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    border:6px solid var(--line);
    padding: 2rem;
    margin-bottom: 2rem;
    color: var(--white);
    animation: slideDown 0.5s ease-out;
}

.filter-section .filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-label {
    font-size: 0.75rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 0.65rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-label i {
    color: var(--accent);
    font-size: 0.85rem;
}

.filter-section .form-control,
.filter-section .form-select {
    background: rgba(255, 255, 255, 0.95) !important;
    color: var(--ink) !important;
    border:3px solid var(--line-strong) !important;
    border-radius: 4px;
    padding: 0.85rem 1rem;
    font-weight: 700;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.2s ease;
}

.filter-section .form-control:focus,
.filter-section .form-select:focus {
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 4px rgba(156, 224, 219, 0.2) !important;
    outline: none !important;
}

.filter-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
}

.filter-btn {
    padding: 0.85rem 1.5rem;
    background: var(--white);
    color: var(--ink);
    border:3px solid var(--line-strong);
    font-weight: 900;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 4px;
}

.filter-btn:hover {
    background: var(--primary);
    color: var(--white);
    transform: translate(-2px, -2px);
    box-shadow: 4px 4px 0 rgba(0, 0, 0, 0.3);
}

.filter-btn:active {
    transform: translate(1px, 1px);
}

/* ============================================================
   EXPORT BUTTONS - HEADER
   ============================================================ */
.export-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn-export {
    padding: 0.85rem 1.25rem;
    border:3px solid var(--line-strong);
    font-weight: 900;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    cursor: pointer;
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 4px;
    text-decoration: none;
}

.btn-export-xls {
    background: var(--success);
    color: var(--white);
}

.btn-export-xls:hover {
    background: var(--success);
    filter: brightness(0.92);
    box-shadow: 6px 6px 0 var(--sun);
    transform: translate(-2px, -2px);
}

.btn-export-pdf {
    background: var(--danger);
    color: var(--white);
}

.btn-export-pdf:hover {
    filter: brightness(0.92);
    box-shadow: 6px 6px 0 var(--accent);
    transform: translate(-2px, -2px);
}

.btn-export-print {
    background: var(--primary);
    color: var(--white);
}

.btn-export-print:hover {
    background: var(--primary);
    box-shadow: 6px 6px 0 var(--accent);
    transform: translate(-2px, -2px);
}

.btn-export:active {
    transform: translate(1px, 1px);
}

/* ============================================================
   TOGGLE CHARTS
   ============================================================ */
.toggle-charts-btn {
    background: var(--primary);
    color: var(--white);
    border:4px solid var(--line);
    padding: 1rem 1.5rem;
    font-weight: 900;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    border-radius: 4px;
    margin-bottom: 2rem;
}

.toggle-charts-btn:hover {
    background: var(--primary);
    box-shadow: 0 0 0 4px var(--accent);
    transform: scale(1.02);
}

.toggle-charts-btn:active {
    transform: scale(0.98);
}

/* ============================================================
   PERIODO INFO
   ============================================================ */
.periodo-info {
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    border:3px solid var(--line-strong);
    border-left: 6px solid var(--accent);
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    color: var(--white);
    font-weight: 800;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    animation: slideDown 0.5s ease-out;
}

.periodo-badge {
    background: var(--accent);
    color: var(--primary-dark);
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 900;
    letter-spacing: 0.5px;
    border:2px solid var(--line-strong);
}

/* ============================================================
   SUMMARY CARDS
   ============================================================ */
.summary-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.summary-card {
    background: var(--white);
    border:6px solid var(--line);
    padding: 2rem 1.75rem;
    text-align: center;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
}

.summary-card:nth-child(2)::before {
    background: linear-gradient(90deg, var(--danger), var(--coral));
}

.summary-card:nth-child(3)::before {
    background: linear-gradient(90deg, var(--success), var(--success));
}

.summary-card:hover {
    box-shadow:
        12px 12px 0 rgba(0, 0, 0, 0.15),
        0 0 0 1px var(--line);
    transform: translate(-4px, -4px);
}

.summary-label {
    font-size: 0.8rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--primary);
    margin-bottom: 0.75rem;
}

.summary-value {
    font-size: 2rem;
    font-weight: 900;
    letter-spacing: -1px;
    margin: 0.5rem 0;
    word-break: break-word;
}

.summary-icon {
    font-size: 2.5rem;
    opacity: 0.1;
    position: absolute;
    top: -10px;
    right: -10px;
    z-index: 0;
}

.summary-content {
    position: relative;
    z-index: 1;
}

/* ============================================================
   CHARTS SECTION
   ============================================================ */
.charts-container {
    animation: fadeIn 0.5s ease-out;
}

.chart-section {
    background: var(--white);
    border:6px solid var(--line);
    padding: 2rem;
    margin-bottom: 2rem;
    border-radius: 4px;
}

.chart-title {
    font-weight: 900;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--ink);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid var(--primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.chart-title i {
    color: var(--primary);
    font-size: 1.2rem;
}

.chart-container {
    position: relative;
    height: 350px;
}

/* ============================================================
   TABLE SECTION
   ============================================================ */
.table-wrapper {
    background: var(--white);
    border:6px solid var(--line);
    overflow: hidden;
    margin-bottom: 0;
    animation: slideUp 0.5s ease-out;
}

.report-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: collapse;
}

.report-table th {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: var(--white);
    font-weight: 900;
    padding: 1.25rem 1rem;
    border:2px solid var(--line-strong);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-align: center;
    white-space: nowrap;
}

.report-table td {
    padding: 0.95rem 1rem;
    border: 1px solid var(--line);
    font-weight: 700;
    vertical-align: middle;
    font-size: 0.95rem;
}

.report-table tbody tr {
    transition: all 0.2s ease;
}

.report-table tbody tr:hover {
    background: var(--surface);
}

.report-table tbody tr.tr-expandido {
    background: var(--sun-soft) !important;
}

.report-table tbody tr.tr-principal {
    cursor: pointer;
}

.report-table .badge {
    font-weight: 900;
    padding: 0.5rem 1rem !important;
    border:2px solid var(--line-strong) !important;
    border-radius: 4px;
}

.badge-ingreso {
    background: var(--mint) !important;
    color: var(--success) !important;
}

.badge-egreso {
    background: var(--coral-soft) !important;
    color: var(--danger) !important;
}

.currency-positive {
    color: var(--success);
    font-weight: 900;
}

.currency-negative {
    color: var(--danger);
    font-weight: 900;
}

.table-expand-icon {
    font-size: 0.8rem;
    opacity: 0.6;
    transition: transform 0.3s ease;
}

.tr-principal.tr-expandido .table-expand-icon {
    transform: rotate(180deg);
}

/* Detail row */
.detalle-row {
    background: var(--surface) !important;
}

.detalle-row td {
    padding: 0 !important;
    border: 2px solid var(--primary) !important;
    border-top: none !important;
}

.detalle-content {
    padding: 1.5rem 1.5rem;
    font-size: 0.9rem;
    line-height: 1.8;
}

.detalle-row .row > div {
    margin-bottom: 0.75rem;
}

.detalle-row strong {
    font-weight: 900;
    color: var(--primary);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.25rem;
}

.table-footer {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    color: var(--white);
    padding: 1.5rem;
    border:6px solid var(--line);
    border-top: none;
    font-weight: 900;
    text-transform: uppercase;
    font-size: 0.95rem;
    letter-spacing: 0.5px;
    display: flex;
    justify-content: flex-end;
    gap: 2rem;
    flex-wrap: wrap;
}

.table-footer-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.table-footer-label {
    opacity: 0.9;
    font-size: 0.85rem;
}

.table-footer-value {
    font-size: 1.1rem;
    letter-spacing: 1px;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    border:6px solid var(--line);
    background: var(--white);
}

.empty-state-icon {
    font-size: 5rem;
    opacity: 0.15;
    margin-bottom: 1rem;
}

.empty-state-title {
    font-size: 1.4rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--primary);
}

.loading-state {
    text-align: center;
    padding: 3rem 2rem;
}

.spinner-border {
    border-width: 4px !important;
    border-color: var(--primary) !important;
    border-right-color: transparent !important;
}

.loading-text {
    margin-top: 1.5rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--primary);
}

/* ============================================================
   ANIMATIONS
   ============================================================ */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    .filter-section {
        padding: 1.5rem;
    }

    .filter-section .filter-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .filter-buttons {
        flex-direction: column;
        width: 100%;
    }

    .filter-buttons .filter-btn {
        width: 100%;
        justify-content: center;
    }

    .summary-section {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .chart-container {
        height: 300px;
    }

    .table-footer {
        flex-direction: column;
        gap: 1rem;
        padding: 1.25rem;
    }

    .report-table th {
        font-size: 0.7rem;
        padding: 0.85rem 0.5rem;
    }

    .report-table td {
        font-size: 0.85rem;
        padding: 0.75rem 0.5rem;
    }

    .export-buttons {
        width: 100%;
    }

    .btn-export {
        flex: 1;
        justify-content: center;
        min-width: auto;
    }
}

@media print {
    body {
        background: var(--white) !important;
    }

    #app-header,
    .no-print {
        display: none !important;
    }

    main {
        margin: 0 !important;
        padding: 0 !important;
    }

    .filter-section,
    .toggle-charts-btn,
    .charts-container {
        display: none !important;
    }

    .table-wrapper,
    .summary-section {
        box-shadow: none !important;
    }
}
</style>
@endpush

@section('content')
<div class="main-container w-full">
    {{-- HEADER --}}
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 bg-white p-4 rounded-3 shadow-heavy no-print">
        <div class="header-decoration">
            <h1 class="fs-title mb-2 text-black">REPORTES</h1>
            <p class="font-bold small text-black uppercase">Informes de gestión operativa y financiera</p>
        </div>
        <div class="export-buttons">
            <button class="btn-export btn-export-xls" onclick="exportXLS()" title="Exportar a Excel">
                <i class="fas fa-file-excel"></i> XLS
            </button>
            <button class="btn-export btn-export-pdf" onclick="generarPDF()" title="Generar PDF">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
            <button class="btn-export btn-export-print" onclick="imprimirReporte()" title="Imprimir">
                <i class="fas fa-print"></i> IMPRIMIR
            </button>
        </div>
    </header>

    {{-- FILTROS --}}
    <div class="filter-section no-print">
        <div class="filter-row">
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-filter"></i> TIPO REPORTE
                </label>
                <select class="form-select" id="filterTipo">
                    <option value="TODO">TODO</option>
                    <option value="GASTOS">SOLO GASTOS</option>
                    <option value="UNIDADES">UNIDADES</option>
                    <option value="USUARIOS">USUARIOS</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-truck"></i> UNIDAD
                </label>
                <select class="form-select" id="filterVehiculo">
                    <option value="">TODAS</option>
                    @foreach($vehiculos as $v)
                        <option value="{{ $v->id_vehiculo }}">{{ $v->placa_vehiculo }} - {{ $v->marca }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-calendar"></i> FECHA INICIO
                </label>
                <input type="date" class="form-control" id="filterFechaInicio" value="{{ date('Y-m-01') }}">
            </div>

            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-calendar"></i> FECHA FIN
                </label>
                <input type="date" class="form-control" id="filterFechaFin" value="{{ date('Y-m-d') }}">
            </div>
        </div>

        <div class="filter-buttons mt-3">
            <button class="filter-btn" onclick="cargarReporte()">
                <i class="fas fa-search"></i> GENERAR REPORTE
            </button>
            <button class="filter-btn" onclick="exportCSV()">
                <i class="fas fa-file-csv"></i> EXPORTAR CSV
            </button>
        </div>
    </div>

    {{-- INFO PERÍODO --}}
    <div id="periodoInfo" class="periodo-info" style="display:none;">
        <div>
            <i class="fas fa-calendar-alt"></i>
            PERÍODO: <span id="periodoText"></span>
        </div>
        <div class="periodo-badge" id="tipoReporteBadge">TODO</div>
    </div>

    {{-- TOGGLE CHARTS --}}
    <div class="no-print">
        <button class="toggle-charts-btn" onclick="toggleCharts()">
            <i class="fas fa-chart-pie"></i>
            <span id="toggleChartsText">OCULTAR GRÁFICAS</span>
        </button>
    </div>

    {{-- GRÁFICAS --}}
    <div id="chartsContainer" class="charts-container no-print">
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="chart-section">
                    <div class="chart-title">
                        <i class="fas fa-chart-pie"></i> GASTOS POR CATEGORÍA
                    </div>
                    <div class="chart-container">
                        <canvas id="chartGastosCategoria"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-section">
                    <div class="chart-title">
                        <i class="fas fa-chart-bar"></i> INGRESOS POR UNIDAD
                    </div>
                    <div class="chart-container">
                        <canvas id="chartIngresosUnidad"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="chart-section">
                    <div class="chart-title">
                        <i class="fas fa-chart-line"></i> EVOLUCIÓN MENSUAL
                    </div>
                    <div class="chart-container">
                        <canvas id="chartEvolucionMensual"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RESUMEN --}}
    <div class="summary-section" id="summaryContainer">
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-arrow-up"></i></div>
            <div class="summary-content">
                <div class="summary-label">TOTAL INGRESOS</div>
                <div class="summary-value currency-positive" id="totalIngresos">Bs. 0.00</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-arrow-down"></i></div>
            <div class="summary-content">
                <div class="summary-label">TOTAL EGRESOS</div>
                <div class="summary-value currency-negative" id="totalEgresos">Bs. 0.00</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-balance-scale"></i></div>
            <div class="summary-content">
                <div class="summary-label">BALANCE NETO</div>
                <div class="summary-value" id="balanceValor">Bs. 0.00</div>
            </div>
        </div>
    </div>

    {{-- TABLA --}}
    <div id="reportLoading" class="loading-state">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="loading-text">GENERANDO REPORTE...</p>
    </div>

    <div id="reportEmpty" class="empty-state" style="display:none;">
        <div class="empty-state-icon">
            <i class="fas fa-chart-bar"></i>
        </div>
        <div class="empty-state-title">NO HAY DATOS PARA EL PERÍODO SELECCIONADO</div>
    </div>

    <div id="reportContainer" style="display:none;">
        <div class="table-wrapper">
            <table class="report-table" id="reportTable">
                <thead>
                    <tr>
                        <th style="width: 10%;">N° DOC</th>
                        <th style="width: 10%;">FECHA</th>
                        <th style="width: 12%;">TIPO</th>
                        <th style="width: 18%;">RECORRIDO</th>
                        <th style="width: 12%;">UNIDAD</th>
                        <th style="width: 12%;">CATEGORÍA</th>
                        <th style="width: 10%;">TN</th>
                        <th style="width: 12%;">INGRESO</th>
                        <th style="width: 12%;">EGRESO</th>
                        <th style="width: 6%;">+</th>
                    </tr>
                </thead>
                <tbody id="reportBody"></tbody>
            </table>
        </div>
        <div class="table-footer">
            <div class="table-footer-item">
                <span class="table-footer-label">TOTAL INGRESOS:</span>
                <span class="table-footer-value" id="footerIngresos">Bs. 0.00</span>
            </div>
            <div class="table-footer-item">
                <span class="table-footer-label">TOTAL EGRESOS:</span>
                <span class="table-footer-value" id="footerEgresos">Bs. 0.00</span>
            </div>
            <div class="table-footer-item">
                <span class="table-footer-label">BALANCE:</span>
                <span class="table-footer-value" id="footerBalance">Bs. 0.00</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let charts = {};
let chartsVisible = true;

// Colores desde la paleta (Chart.js no acepta var()); variables de app-vibrant.css
const ROOT_CSS = getComputedStyle(document.documentElement);
const PALETA_CSS = {
    primary:       ROOT_CSS.getPropertyValue('--primary').trim()        || '#2f2c79',
    primaryLight:  ROOT_CSS.getPropertyValue('--primary-light').trim()  || '#4b47b8',
    sun:           ROOT_CSS.getPropertyValue('--sun').trim()            || '#ffc857',
    success:       ROOT_CSS.getPropertyValue('--success').trim()        || '#1f9d55',
    info:          ROOT_CSS.getPropertyValue('--info').trim()           || '#0ea5b7',
    danger:        ROOT_CSS.getPropertyValue('--danger').trim()         || '#d6336c',
    coral:         ROOT_CSS.getPropertyValue('--coral').trim()          || '#ff7a6b',
    lilac:         ROOT_CSS.getPropertyValue('--lilac').trim()          || '#b7a7ff',
    line:          ROOT_CSS.getPropertyValue('--line-strong').trim()    || '#a3a9c9',
    surfaceLine:   ROOT_CSS.getPropertyValue('--line').trim()           || '#dcdff0'
};

document.addEventListener('DOMContentLoaded', () => {
    cargarReporte();
    cargarEstadisticas();
});

function toggleCharts() {
    chartsVisible = !chartsVisible;
    document.getElementById('chartsContainer').style.display = chartsVisible ? 'block' : 'none';
    document.getElementById('toggleChartsText').textContent = chartsVisible ? 'OCULTAR GRÁFICAS' : 'MOSTRAR GRÁFICAS';
}

function cargarEstadisticas() {
    const params = new URLSearchParams();
    params.append('fecha_inicio', document.getElementById('filterFechaInicio').value);
    params.append('fecha_fin', document.getElementById('filterFechaFin').value);
    const vid = document.getElementById('filterVehiculo').value;
    if (vid) params.append('id_vehiculo', vid);

    fetch('{{ url("api/reportes/estadisticas") }}?' + params.toString(), {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        renderGastosCategoria(res.data.gastos_por_categoria);
        renderIngresosUnidad(res.data.ingresos_por_vehiculo);
        renderEvolucionMensual(res.data.por_mes);
    })
    .catch(err => console.error('Error:', err));
}

function renderGastosCategoria(data) {
    const ctx = document.getElementById('chartGastosCategoria').getContext('2d');
    if (charts.gastosCategoria) charts.gastosCategoria.destroy();

    const labels = Object.keys(data);
    const values = labels.map(k => data[k].total);
    const colors = [PALETA_CSS.primary, PALETA_CSS.danger, PALETA_CSS.sun, PALETA_CSS.success, PALETA_CSS.info, PALETA_CSS.lilac, PALETA_CSS.coral, PALETA_CSS.primaryLight];

    charts.gastosCategoria = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderColor: PALETA_CSS.line,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { weight: 'bold', size: 12 },
                        padding: 15,
                        boxPadding: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': Bs. ' + context.parsed.toFixed(2);
                        }
                    },
                    titleFont: { weight: 'bold', size: 12 },
                    bodyFont: { weight: 'bold' },
                    padding: 12
                }
            }
        }
    });
}

function renderIngresosUnidad(data) {
    const ctx = document.getElementById('chartIngresosUnidad').getContext('2d');
    if (charts.ingresosUnidad) charts.ingresosUnidad.destroy();

    const labels = Object.keys(data);
    const values = labels.map(k => data[k].total);

    charts.ingresosUnidad = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ingresos (Bs.)',
                data: values,
                backgroundColor: PALETA_CSS.primary,
                borderColor: PALETA_CSS.line,
                borderWidth: 3,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Bs. ' + context.parsed.y.toFixed(2);
                        }
                    },
                    titleFont: { weight: 'bold', size: 12 },
                    bodyFont: { weight: 'bold' },
                    padding: 12
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { weight: 'bold' },
                        callback: function(value) {
                            return 'Bs. ' + value.toLocaleString();
                        }
                    },
                    grid: { color: PALETA_CSS.surfaceLine }
                },
                x: {
                    ticks: { font: { weight: 'bold' } }
                }
            }
        }
    });
}

function renderEvolucionMensual(data) {
    const ctx = document.getElementById('chartEvolucionMensual').getContext('2d');
    if (charts.evolucionMensual) charts.evolucionMensual.destroy();

    const labels = data.map(d => d.mes);
    const ingresos = data.map(d => d.ingresos);
    const gastos = data.map(d => d.gastos);

    charts.evolucionMensual = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Ingresos',
                    data: ingresos,
                    backgroundColor: PALETA_CSS.success,
                    borderColor: PALETA_CSS.line,
                    borderWidth: 3,
                    borderRadius: 4
                },
                {
                    label: 'Gastos',
                    data: gastos,
                    backgroundColor: PALETA_CSS.danger,
                    borderColor: PALETA_CSS.line,
                    borderWidth: 3,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { weight: 'bold' }, padding: 15 }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Bs. ' + context.parsed.y.toFixed(2);
                        }
                    },
                    titleFont: { weight: 'bold', size: 12 },
                    bodyFont: { weight: 'bold' },
                    padding: 12
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { weight: 'bold' },
                        callback: function(value) {
                            return 'Bs. ' + value.toLocaleString();
                        }
                    },
                    grid: { color: PALETA_CSS.surfaceLine }
                },
                x: {
                    ticks: { font: { weight: 'bold' } }
                }
            }
        }
    });
}

function cargarReporte() {
    const params = new URLSearchParams();
    params.append('tipo', document.getElementById('filterTipo').value);
    params.append('fecha_inicio', document.getElementById('filterFechaInicio').value);
    params.append('fecha_fin', document.getElementById('filterFechaFin').value);
    const vid = document.getElementById('filterVehiculo').value;
    if (vid) params.append('id_vehiculo', vid);

    document.getElementById('reportLoading').style.display = 'block';
    document.getElementById('reportContainer').style.display = 'none';
    document.getElementById('reportEmpty').style.display = 'none';

    cargarEstadisticas();

    fetch('{{ url("api/reportes/filtro") }}?' + params.toString(), {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('reportLoading').style.display = 'none';
        if (!res.success) return;

        document.getElementById('periodoInfo').style.display = 'flex';
        document.getElementById('periodoText').textContent = res.resumen.periodo;
        document.getElementById('tipoReporteBadge').textContent = res.resumen.tipo_reporte;

        const ti = parseFloat(res.resumen.total_ingresos || 0);
        const te = parseFloat(res.resumen.total_egresos || 0);
        const balance = ti - te;
        document.getElementById('totalIngresos').textContent = formatCurrency(ti);
        document.getElementById('totalEgresos').textContent = formatCurrency(te);
        const balEl = document.getElementById('balanceValor');
        balEl.textContent = formatCurrency(balance);
        balEl.className = balance >= 0 ? 'summary-value currency-positive' : 'summary-value currency-negative';

        if (!res.data || res.data.length === 0) {
            document.getElementById('reportEmpty').style.display = 'block';
            return;
        }
        document.getElementById('reportContainer').style.display = 'block';

        const tbody = document.getElementById('reportBody');
        tbody.innerHTML = res.data.map((r, i) => {
            const tipoClass = r.tipo_registro === 'INGRESO' ? 'badge-ingreso' : 'badge-egreso';
            const tipoIcon = r.tipo_registro === 'INGRESO' ? '↑' : '↓';
            const recorrido = r.tipo_registro === 'INGRESO'
                ? ((r.origen || '') + (r.destino ? ' → ' + r.destino : ''))
                : (r.concepto || '—');
            const ingresoHtml = r.tipo_registro === 'INGRESO' ? `<span class="currency-positive">${formatCurrency(r.ingreso)}</span>` : '—';
            const egresoHtml = r.tipo_registro === 'GASTO' ? `<span class="currency-negative">${formatCurrency(r.egreso)}</span>` : '—';
            const cat = r.tipo_registro === 'INGRESO' ? 'FLETE' : (r.tipo_gasto || '—');
            return `<tr class="tr-principal tr-principal-${i}" onclick="toggleDetalle(${i})">
                <td class="fw-bold font-monospace">${r.nro_documento || '—'}</td>
                <td>${r.fecha || '—'}</td>
                <td class="text-center">
                    <span class="badge ${tipoClass}">
                        ${tipoIcon} ${r.tipo_registro === 'INGRESO' ? 'ING' : 'EGR'}
                    </span>
                </td>
                <td class="fw-bold">${recorrido || '—'}</td>
                <td>${r.placa_vehiculo || '—'}</td>
                <td>${cat}</td>
                <td class="text-center">${r.tipo_registro === 'INGRESO' ? (r.toneladas || '—') : '—'}</td>
                <td class="text-end">${ingresoHtml}</td>
                <td class="text-end">${egresoHtml}</td>
                <td class="text-center"><span class="table-expand-icon">▼</span></td>
            </tr>
            <tr id="detalle-${i}" class="detalle-row" style="display:none;">
                <td colspan="10">
                    <div class="detalle-content">
                        ${r.tipo_registro === 'INGRESO' ? `
                            <div class="row">
                                <div class="col-md-3"><strong>CLIENTE:</strong> ${r.cliente_nombre || '—'}</div>
                                <div class="col-md-3"><strong>CONDUCTOR:</strong> ${r.conductor_asignado || '—'}</div>
                                <div class="col-md-2"><strong>TN:</strong> ${r.toneladas || '0'}</div>
                                <div class="col-md-2"><strong>PAGO:</strong> ${r.tipo_pago || '—'}</div>
                                <div class="col-md-2"><strong>OBS:</strong> ${r.observaciones || '—'}</div>
                            </div>
                        ` : `
                            <div class="row">
                                <div class="col-md-3"><strong>TIPO:</strong> ${r.tipo_gasto || '—'}</div>
                                <div class="col-md-3"><strong>PROVEEDOR:</strong> ${r.proveedor || '—'}</div>
                                <div class="col-md-2"><strong>KM:</strong> ${r.kilometraje || '—'}</div>
                                <div class="col-md-4"><strong>OBS:</strong> ${r.observaciones || '—'}</div>
                            </div>
                        `}
                    </div>
                </td>
            </tr>`;
        }).join('');
        window._reportData = res.data;

        document.getElementById('footerIngresos').textContent = formatCurrency(ti);
        document.getElementById('footerEgresos').textContent = formatCurrency(te);
        const fb = document.getElementById('footerBalance');
        fb.textContent = formatCurrency(balance);
    })
    .catch(err => console.error('Error:', err));
}

function toggleDetalle(index) {
    const row = document.getElementById('detalle-' + index);
    if (!row) return;
    const isHidden = row.style.display === 'none';
    row.style.display = isHidden ? 'table-row' : 'none';
    const tr = document.querySelector('.tr-principal-' + index);
    if (tr) tr.classList.toggle('tr-expandido', isHidden);
}

function generarPDF() {
    const params = new URLSearchParams();
    params.append('tipo', document.getElementById('filterTipo').value);
    params.append('fecha_inicio', document.getElementById('filterFechaInicio').value);
    params.append('fecha_fin', document.getElementById('filterFechaFin').value);
    const vid = document.getElementById('filterVehiculo').value;
    if (vid) params.append('id_vehiculo', vid);
    window.open('{{ url("reportes/pdf") }}?' + params.toString(), '_blank');
}

function imprimirReporte() {
    const params = new URLSearchParams();
    params.append('tipo', document.getElementById('filterTipo').value);
    params.append('fecha_inicio', document.getElementById('filterFechaInicio').value);
    params.append('fecha_fin', document.getElementById('filterFechaFin').value);
    const vid = document.getElementById('filterVehiculo').value;
    if (vid) params.append('id_vehiculo', vid);
    window.open('{{ url("reportes/imprimir") }}?' + params.toString(), '_blank');
}

function exportCSV() {
    const tbody = document.getElementById('reportBody');
    if (!tbody) return;
    let csv = '\uFEFF';
    csv += '"N° DOC","FECHA","TIPO","RECORRIDO","UNIDAD","CATEGORÍA","TONELADAS","INGRESO","EGRESO"\n';
    tbody.querySelectorAll('tr.tr-principal').forEach(row => {
        const cols = row.querySelectorAll('td');
        const rowData = [];
        for (let i = 0; i < Math.min(cols.length, 9); i++) {
            let txt = cols[i].textContent.trim().replace(/,/g, '');
            rowData.push('"' + txt + '"');
        }
        csv += rowData.join(',') + '\n';
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'reporte_' + new Date().toISOString().split('T')[0] + '.csv';
    link.click();
}

function exportXLS() {
    const tbody = document.getElementById('reportBody');
    if (!tbody) return;
    let html = '<table border="1"><thead><tr>';
    html += '<th>N° DOC</th><th>FECHA</th><th>TIPO</th><th>RECORRIDO</th><th>UNIDAD</th><th>CATEGORÍA</th><th>TONELADAS</th><th>INGRESO</th><th>EGRESO</th>';
    html += '</tr></thead><tbody>';
    tbody.querySelectorAll('tr.tr-principal').forEach(row => {
        const cols = row.querySelectorAll('td');
        html += '<tr>';
        for (let i = 0; i < Math.min(cols.length, 9); i++) {
            let txt = cols[i].textContent.trim().replace(/[^\w\sáéíóúÁÉÍÓÚñÑ.,Bs()\-→/]/g, '');
            html += '<td>' + txt + '</td>';
        }
        html += '</tr>';
    });
    html += '</tbody></table>';
    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'reporte_' + new Date().toISOString().split('T')[0] + '.xls';
    link.click();
}

function formatCurrency(val) {
    return 'Bs. ' + parseFloat(val || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
</script>
@endpush
