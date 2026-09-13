@extends('layouts.master')

@section('title', 'Gastos Generales')

@push('styles')
<style>
.gastos-table th {
    background: #000 !important;
    color: #fff !important;
    font-weight: 800;
    padding: 12px 10px;
    border: 2px solid #000;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.gastos-table td {
    padding: 10px;
    border: 1px solid #000;
    font-weight: 600;
    vertical-align: middle;
}
.gastos-table tbody tr:hover {
    background: #fff8e1;
}
.filter-card {
    background: #2f2c79;
    border: 4px solid #000;
    padding: 20px;
}
.filter-card label {
    font-weight: 800;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #fff;
}
.filter-card .form-control, .filter-card .form-select {
    border-radius: 0;
    border: 3px solid #000;
    padding: 10px 12px;
    font-weight: 700;
}
</style>
@endpush

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">GASTOS GENERALES</h1>
            <p class="font-bold small text-black uppercase">Caja Chica, Servicios Básicos, Impuestos y más</p>
        </div>
        <a href="{{ route('gastos-generales.create') }}" class="btn-bento btn-bento-primary py-1 px-3 fs-mid font-bold rounded-3 text-decoration-none" style="border:3px solid #000;">
            <i class="fas fa-plus me-1"></i> NUEVO GASTO
        </a>
    </header>

    <!-- FILTROS -->
    <div class="filter-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label>CATEGORÍA</label>
                <select class="form-select" id="filterCategoria">
                    <option value="">TODAS</option>
                    <option value="Caja Chiva">CAJA CHICA</option>
                    <option value="Servicios Básicos">SERVICIOS BÁSICOS</option>
                    <option value="Impuestos">IMPUESTOS</option>
                    <option value="Telecomunicaciones">TELECOMUNICACIONES</option>
                    <option value="Alquiler">ALQUILER</option>
                    <option value="Seguros">SEGUROS</option>
                    <option value="Varios">VARIOS</option>
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
                <button class="btn fw-bold w-100" style="background:#000;color:#fff;border:3px solid #000;padding:10px;border-radius:0;" onclick="cargarGastos()">
                    <i class="fas fa-search me-1"></i> BUSCAR
                </button>
            </div>
            <div class="col-md-3 text-end">
                <span class="badge bg-black text-warning px-3 py-2 fw-bold fs-6" id="totalGastos">TOTAL: Bs. 0.00</span>
            </div>
        </div>
    </div>

    <!-- TABLA -->
    <div id="loadingGastos" class="text-center py-5">
        <div class="spinner-border text-dark" role="status"></div>
        <p class="mt-3 fw-bold">CARGANDO...</p>
    </div>
    <div id="emptyGastos" class="text-center py-5" style="display:none;border:4px solid #000;">
        <i class="fas fa-receipt" style="font-size:64px;opacity:.2;"></i>
        <h3 class="mt-3 fw-bold">NO HAY GASTOS REGISTRADOS</h3>
    </div>
    <div id="tableContainer" style="display:none;">
        <div style="border:4px solid #000;overflow-x:auto;">
            <table class="gastos-table w-100 mb-0">
                <thead>
                    <tr>
                        <th>N° DOC</th>
                        <th>FECHA</th>
                        <th>CATEGORÍA</th>
                        <th>CONCEPTO</th>
                        <th>MONTO</th>
                        <th>PAGO</th>
                        <th>BANCO</th>
                        <th>PROVEEDOR</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="gastosBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', cargarGastos);

function cargarGastos() {
    const params = new URLSearchParams();
    const cat = document.getElementById('filterCategoria').value;
    if (cat) params.append('categoria', cat);
    params.append('fecha_inicio', document.getElementById('filterFechaInicio').value);
    params.append('fecha_fin', document.getElementById('filterFechaFin').value);

    document.getElementById('loadingGastos').style.display = 'block';
    document.getElementById('tableContainer').style.display = 'none';
    document.getElementById('emptyGastos').style.display = 'none';

    fetch('{{ url("api/gastos-generales") }}?' + params.toString(), {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('loadingGastos').style.display = 'none';
        if (!res.success || !res.data.length) {
            document.getElementById('emptyGastos').style.display = 'block';
            return;
        }
        document.getElementById('tableContainer').style.display = 'block';

        const total = res.data.reduce((sum, g) => sum + parseFloat(g.monto), 0);
        document.getElementById('totalGastos').textContent = 'TOTAL: Bs. ' + total.toFixed(2);

        const tbody = document.getElementById('gastosBody');
        tbody.innerHTML = res.data.map(g => {
            const estadoBadge = g.estado === 'ACTIVO' 
                ? '<span style="background:#d4edda;color:#155724;border:2px solid #000;padding:4px 8px;font-weight:800;">ACTIVO</span>'
                : '<span style="background:#f8d7da;color:#721c24;border:2px solid #000;padding:4px 8px;font-weight:800;">ANULADO</span>';
            return `<tr>
                <td class="fw-bold font-monospace" style="white-space:nowrap;">${g.nro_documento}</td>
                <td style="white-space:nowrap;">${g.fecha_gasto}</td>
                <td><span class="badge" style="background:#2f2c79;color:#fff;border:2px solid #000;padding:4px 8px;">${g.categoria}</span></td>
                <td class="fw-bold">${g.concepto}</td>
                <td class="fw-bold" style="color:#dc3545;">Bs. ${parseFloat(g.monto).toFixed(2)}</td>
                <td>${g.condicion_pago}</td>
                <td>${g.nombre_banco || '—'}</td>
                <td>${g.nombre_proveedor || '—'}</td>
                <td>${estadoBadge}</td>
                <td>
                    <a href="${'{{ url("gastos-generales") }}'}/${g.id_gasto_general}/editar" class="btn btn-sm" style="background:#ffc107;border:2px solid #000;">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button class="btn btn-sm" style="background:#dc3545;border:2px solid #000;color:#fff;" onclick="eliminarGasto(${g.id_gasto_general})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');
    });
}

function eliminarGasto(id) {
    Swal.fire({
        title: '¿ELIMINAR GASTO?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'SÍ, ELIMINAR',
        cancelButtonText: 'CANCELAR'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('{{ url("api/gastos-generales") }}/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire('ELIMINADO', 'Gasto eliminado correctamente', 'success');
                    cargarGastos();
                }
            });
        }
    });
}
</script>
@endsection
