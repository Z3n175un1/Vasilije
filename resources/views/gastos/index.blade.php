@extends('layouts.master')

@section('title', 'Gastos - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">GASTOS</h1>
            <p class="font-bold small text-black uppercase">Registro de Egresos Operativos</p>
        </div>
        <a href="{{ route('gastos.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVO GASTO
        </a>
    </header>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <select class="form-control fw-bold" id="filtroVehiculo" style="border-radius:0;border:3px solid #000;padding:10px;">
                <option value="">TODOS LOS VEHÍCULOS</option>
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-control fw-bold" id="filtroTipo" style="border-radius:0;border:3px solid #000;padding:10px;">
                <option value="">TODOS LOS TIPOS</option>
                @foreach(['Combustible', 'Mantenimiento', 'Peaje', 'Sueldo', 'Viático', 'Seguro', 'Lubricante', 'Llantas', 'Otro'] as $tipo)
                    <option value="{{ $tipo }}">{{ $tipo }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <button class="btn fw-bold w-100" style="border:3px solid #000;background:#000;color:#ffc107;padding:10px;" onclick="cargarGastos()">
                <i class="fas fa-search"></i> FILTRAR
            </button>
        </div>
    </div>

    <div class="bento-card" style="padding: 0; overflow: hidden; border: 4px solid #000; box-shadow: 6px 6px 0px #000;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span class="small uppercase font-bold text-black"><i class="fas fa-minus-circle me-2"></i> Últimos Gastos</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>FECHA</th>
                        <th>N° DOC</th>
                        <th>UNIDAD</th>
                        <th>TIPO</th>
                        <th>CONCEPTO</th>
                        <th>MONTO</th>
                        <th>PROVEEDOR</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="gastosList">
                    <tr><td colspan="8" class="text-center py-5 opacity-50">CARGANDO...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let vehiculos = [];

document.addEventListener('DOMContentLoaded', function() {
    loadCombos();
    cargarGastos();
});

function loadCombos() {
    fetch('{{ url("api/vehiculos") }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        vehiculos = res.data || [];
        const sel = document.getElementById('filtroVehiculo');
        sel.innerHTML = '<option value="">TODOS LOS VEHÍCULOS</option>' +
            vehiculos.map(v => `<option value="${v.id_vehiculo}">${v.placa_vehiculo}</option>`).join('');
    });
}

function cargarGastos() {
    const params = new URLSearchParams();
    const idV = document.getElementById('filtroVehiculo').value;
    const tipo = document.getElementById('filtroTipo').value;
    if (idV) params.append('id_vehiculo', idV);
    if (tipo) params.append('tipo_gasto', tipo);

    const tbody = document.getElementById('gastosList');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>CARGANDO...</td></tr>';

    fetch('{{ url("api/gastos") }}?' + params.toString(), {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success || !res.data) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-danger fw-bold">ERROR AL CARGAR</td></tr>';
            return;
        }
        if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 opacity-50 fw-bold">NO HAY GASTOS REGISTRADOS</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(g => `
            <tr>
                <td class="fw-bold" style="white-space:nowrap;">${g.fecha_gasto || '—'}</td>
                <td class="fw-bold font-monospace">${g.nro_documento || '—'}</td>
                <td class="fw-bold">${g.placa || '—'}</td>
                <td><span class="badge fw-bold px-2 py-1" style="border:2px solid #000;background:#f0f0f0;color:#000;">${g.tipo_gasto || '—'}</span></td>
                <td>${g.concepto || '—'}</td>
                <td class="fw-bold" style="color:#cc0000;">${formatCurrency(g.monto)}</td>
                <td>${g.proveedor || '—'}</td>
                <td>
                    <div class="d-flex gap-1 justify-content-center">
                        <a href="{{ url('gastos') }}/${g.id_gasto}/editar" class="btn btn-sm btn-warning border-black fw-bold" title="EDITAR"><i class="fas fa-edit"></i></a>
                        <button class="btn btn-sm btn-danger border-black fw-bold" onclick="eliminarGasto(${g.id_gasto})" title="ELIMINAR"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    })
    .catch(() => {
        document.getElementById('gastosList').innerHTML = '<tr><td colspan="8" class="text-center py-4 text-danger fw-bold">ERROR DE CONEXIÓN</td></tr>';
    });
}

function eliminarGasto(id) {
    Swal.fire({
        title: 'ELIMINAR GASTO',
        text: '¿Está seguro de eliminar este gasto?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ELIMINAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("gastos") }}/' + id, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => {
                if (r.redirected) window.location.href = r.url;
                else cargarGastos();
            });
        }
    });
}

function formatCurrency(val) {
    return 'Bs. ' + parseFloat(val || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
</script>
@endpush
