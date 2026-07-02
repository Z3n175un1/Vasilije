@extends('layouts.master')

@section('title', 'Reportes - VASILIJE')

@push('styles')
<style>
.report-table th {
    background: #000 !important;
    color: #ffc107 !important;
    font-weight: 800;
    padding: 12px 10px;
    border: 2px solid #000;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.report-table td {
    padding: 10px;
    border: 1px solid #000;
    font-weight: 600;
    vertical-align: middle;
}
.report-table tbody tr:nth-child(even) {
    background: #f8f9fa;
}
.report-table tbody tr:hover {
    background: #fff8e1;
}
.summary-card {
    border: 4px solid #000;
    padding: 20px;
    text-align: center;
    background: #fff;
}
.summary-card .label {
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #666;
}
.summary-card .value {
    font-size: 1.8rem;
    font-weight: 900;
    margin-top: 5px;
}
.filter-card {
    background: #ffc107;
    border: 4px solid #000;
    padding: 20px;
}
.filter-card label {
    font-weight: 800;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.filter-card .form-control, .filter-card .form-select {
    border-radius: 0;
    border: 3px solid #000;
    padding: 10px 12px;
    font-weight: 700;
}
.detalle-row td {
    transition: all 0.2s ease;
}
.tr-expandido {
    background: #fffde7 !important;
}
@media print {
    body { background: #fff !important; }
    #app-header, .no-print { display: none !important; }
    main { margin: 0 !important; padding: 0 !important; }
}
</style>
@endpush

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy no-print">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">REPORTES</h1>
            <p class="font-bold small text-black uppercase">Informes de Gestión Operativa y Financiera</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn-bento btn-bento-outline border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale" style="background:#007400;color:#fff;border:3px solid #000;padding:10px;border-radius:0;" onclick="exportXLS()">
                    <i class="fas fa-file-excel"></i> XLS
            </button>
            <button class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale" onclick="generarPDF()">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </button>
            <button class="btn-bento btn-bento-outline border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale" onclick="window.print()">
                <i class="fas fa-print me-1"></i> IMPRIMIR
            </button>
        </div>
    </header>

    <!-- FILTROS -->
    <div class="filter-card mb-4 no-print">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label>TIPO REPORTE</label>
                <select class="form-select" id="filterTipo">
                    <option value="TODO">TODO</option>
                    <option value="GASTOS">SOLO GASTOS</option>
                    <option value="UNIDADES">UNIDADES</option>
                    <option value="USUARIOS">USUARIOS</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>UNIDAD</label>
                <select class="form-select" id="filterVehiculo">
                    <option value="">TODAS</option>
                    @foreach($vehiculos as $v)
                        <option value="{{ $v->id_vehiculo }}">{{ $v->placa_vehiculo }} - {{ $v->marca }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>FECHA INICIO</label>
                <input type="date" class="form-control" id="filterFechaInicio" value="{{ date('Y-m-01') }}">
            </div>
            <div class="col-md-2">
                <label>FECHA FIN</label>
                <input type="date" class="form-control" id="filterFechaFin" value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <button class="btn fw-bold w-100 d-flex align-items-center justify-content-center gap-2" style="background:#000;color:#ffc107;border:3px solid #000;padding:10px;border-radius:0;" onclick="cargarReporte()">
                    <i class="fas fa-search"></i> GENERAR
                </button>
            </div>
            <div class="col-md-2">
                <button class="btn fw-bold w-100 d-flex align-items-center justify-content-center gap-2" style="background:#000;color:#ffc107;border:3px solid #000;padding:10px;border-radius:0;" onclick="exportCSV()">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
            </div>
            
        </div>
    </div>

    <!-- RESUMEN -->
    <div id="summaryContainer" class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="summary-card">
                <div class="label">TOTAL INGRESOS</div>
                <div class="value" style="color:#007400;" id="totalIngresos">Bs. 0.00</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card">
                <div class="label">TOTAL EGRESOS</div>
                <div class="value" style="color:#dc3545;" id="totalEgresos">Bs. 0.00</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card">
                <div class="label">BALANCE NETO</div>
                <div class="value" id="balanceValor">Bs. 0.00</div>
            </div>
        </div>
    </div>

    <!-- INFO PERIODO -->
    <div id="periodoInfo" class="fw-bold mb-3 px-1" style="display:none;">
        <i class="fas fa-calendar-alt me-2"></i> PERIODO: <span id="periodoText"></span>
        <span class="ms-4 badge bg-black text-warning px-3 py-2" id="tipoReporteBadge">TODO</span>
    </div>

    <!-- TABLA DATOS -->
    <div id="reportLoading" class="text-center py-5">
        <div class="spinner-border text-dark" role="status"></div>
        <p class="mt-3 fw-bold">GENERANDO REPORTE...</p>
    </div>
    <div id="reportEmpty" class="text-center py-5" style="display:none;border:4px solid #000;">
        <i class="fas fa-chart-bar" style="font-size:64px;opacity:.2;"></i>
        <h3 class="mt-3 fw-bold">NO HAY DATOS PARA EL PERÍODO SELECCIONADO</h3>
    </div>
    <div id="reportContainer" style="display:none;">
        <div style="border:4px solid #000;overflow:hidden;">
            <table class="report-table w-100 mb-0" id="reportTable">
                <thead>
                    <tr>
                        <th>N° DOC</th>
                        <th>FECHA</th>
                        <th>TIPO</th>
                        <th>RECORRIDO</th>
                        <th>UNIDAD</th>
                        <th>CATEGORÍA</th>
                        <th>PROVEEDOR</th>
                        <th>INGRESO</th>
                        <th>EGRESO</th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody id="reportBody"></tbody>
            </table>
        </div>
        <div class="fw-bold p-3 text-end" style="background:#000;color:#ffc107;border:4px solid #000;border-top:none;font-size:1.1rem;">
            TOTAL INGRESOS: <span id="footerIngresos">Bs. 0.00</span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            TOTAL EGRESOS: <span id="footerEgresos">Bs. 0.00</span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            BALANCE: <span id="footerBalance">Bs. 0.00</span>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', cargarReporte);

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

    fetch('{{ url("api/reportes/filtro") }}?' + params.toString(), {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('reportLoading').style.display = 'none';
        if (!res.success) return;

        // Periodo
        document.getElementById('periodoInfo').style.display = 'block';
        document.getElementById('periodoText').textContent = res.resumen.periodo;
        document.getElementById('tipoReporteBadge').textContent = res.resumen.tipo_reporte;

        // Summary
        const ti = parseFloat(res.resumen.total_ingresos || 0);
        const te = parseFloat(res.resumen.total_egresos || 0);
        const balance = ti - te;
        document.getElementById('totalIngresos').textContent = formatCurrency(ti);
        document.getElementById('totalEgresos').textContent = formatCurrency(te);
        const balEl = document.getElementById('balanceValor');
        balEl.textContent = formatCurrency(balance);
        balEl.style.color = balance >= 0 ? '#007400' : '#dc3545';

        // Data
        if (!res.data || res.data.length === 0) {
            document.getElementById('reportEmpty').style.display = 'block';
            return;
        }
        document.getElementById('reportContainer').style.display = 'block';

        const tbody = document.getElementById('reportBody');
        tbody.innerHTML = res.data.map((r, i) => {
            const bg = r.tipo_registro === 'INGRESO' ? '#e2ffd6' : '#ffdcd6';
            const recorrido = r.tipo_registro === 'INGRESO'
                ? ((r.origen || '') + (r.destino ? ' → ' + r.destino : ''))
                : (r.concepto || '—');
            const ingresoHtml = r.tipo_registro === 'INGRESO' ? `<span style="color:#007400;font-weight:700;">${formatCurrency(r.ingreso)}</span>` : '—';
            const egresoHtml = r.tipo_registro === 'GASTO' ? `<span style="color:#cc0000;font-weight:700;">${formatCurrency(r.egreso)}</span>` : '—';
            const cat = r.tipo_registro === 'INGRESO' ? 'FLETE' : (r.tipo_gasto || '—');
            return `<tr class="tr-principal tr-principal-${i}" style="cursor:pointer;" onclick="toggleDetalle(${i})">
                <td class="fw-bold font-monospace" style="white-space:nowrap;">${r.nro_documento || '—'}</td>
                <td style="white-space:nowrap;">${r.fecha || '—'}</td>
                <td><span class="badge fw-bold px-3 py-2" style="background:${bg};color:#000;border:2px solid #000;">${r.tipo_registro === 'INGRESO' ? '↑ INGRESO' : '↓ EGRESO'}</span></td>
                <td class="fw-bold">${recorrido || '—'}</td>
                <td>${r.placa_vehiculo || '—'}</td>
                <td>${cat}</td>
                <td>${r.proveedor || '—'}</td>
                <td class="fw-bold">${ingresoHtml}</td>
                <td class="fw-bold">${egresoHtml}</td>
                <td class="text-center fw-bold" style="font-size:0.8rem;">▼</td>
            </tr>
            <tr id="detalle-${i}" class="detalle-row" style="display:none;">
                <td colspan="10" style="padding:0;border:2px solid #000;border-top:none;background:#f9f9f9;">
                    <div style="padding:14px 18px;font-size:0.9rem;">
                        ${r.tipo_registro === 'INGRESO' ? `
                            <div class="row">
                                <div class="col-md-3 mb-2"><strong>CLIENTE:</strong> ${r.cliente_nombre || '—'}</div>
                                <div class="col-md-3 mb-2"><strong>CONDUCTOR:</strong> ${r.conductor_asignado || '—'}</div>
                                <div class="col-md-2 mb-2"><strong>TN:</strong> ${r.toneladas || '0'}</div>
                                <div class="col-md-2 mb-2"><strong>PAGO:</strong> ${r.tipo_pago || '—'}</div>
                                <div class="col-md-2 mb-2"><strong>OBS:</strong> ${r.observaciones || '—'}</div>
                            </div>
                        ` : `
                            <div class="row">
                                <div class="col-md-3 mb-2"><strong>TIPO:</strong> ${r.tipo_gasto || '—'}</div>
                                <div class="col-md-3 mb-2"><strong>PROVEEDOR:</strong> ${r.proveedor || '—'}</div>
                                <div class="col-md-2 mb-2"><strong>KM:</strong> ${r.kilometraje || '—'}</div>
                                <div class="col-md-4 mb-2"><strong>OBS:</strong> ${r.observaciones || '—'}</div>
                            </div>
                        `}
                    </div>
                </td>
            </tr>`;
        }).join('');
        window._reportData = res.data;

        // Footer
        document.getElementById('footerIngresos').textContent = formatCurrency(ti);
        document.getElementById('footerEgresos').textContent = formatCurrency(te);
        const fb = document.getElementById('footerBalance');
        fb.textContent = formatCurrency(balance);
        fb.style.color = balance >= 0 ? '#ffc107' : '#ff6b6b';
    });
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

    const url = '{{ url("reportes/pdf") }}?' + params.toString();
    window.open(url, '_blank');
}

function exportCSV() {
    const tbody = document.getElementById('reportBody');
    if (!tbody) return;
    let csv = '\uFEFF';
    csv += '"N° DOC","FECHA","TIPO","RECORRIDO","UNIDAD","CATEGORÍA","PROVEEDOR","INGRESO","EGRESO"\n';
    tbody.querySelectorAll('tr.tr-principal').forEach(row => {
        const cols = row.querySelectorAll('td');
        const rowData = [];
        for (let i = 0; i < Math.min(cols.length, 10); i++) {
            let txt = cols[i].textContent.trim().replace(/,/g, '');
            if (i === 8) txt = txt.replace('—', '');
            if (i === 7) txt = txt.replace('—', '');
            rowData.push('"' + txt + '"');
        }
        csv += rowData.slice(0, 9).join(',') + '\n';
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
    html += '<th>N° DOC</th><th>FECHA</th><th>TIPO</th><th>RECORRIDO</th><th>UNIDAD</th><th>CATEGORÍA</th><th>PROVEEDOR</th><th>INGRESO</th><th>EGRESO</th>';
    html += '</tr></thead><tbody>';
    tbody.querySelectorAll('tr.tr-principal').forEach(row => {
        const cols = row.querySelectorAll('td');
        html += '<tr>';
        for (let i = 0; i < Math.min(cols.length, 10); i++) {
            let txt = cols[i].textContent.trim().replace(/[^\w\sáéíóúÁÉÍÓÚñÑ.,Bs()\-/]/g, '');
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
