@extends('layouts.master')

@section('title', 'Vehículos - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy animate-slide-up">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">VEHÍCULOS</h1>
            <p class="font-bold small text-black uppercase">Gestión de Unidades de Transporte</p>
        </div>
        <a href="{{ route('vehiculos.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVA UNIDAD
        </a>
    </header>

    <div class="bento-card" style="padding: 0; overflow: hidden; border: 4px solid #000; box-shadow: 6px 6px 0px #000;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span class="small uppercase font-bold text-black"><i class="fas fa-truck me-2"></i> Todas las Unidades</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Tara</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="vehiculosList">
                    <tr><td colspan="7" class="text-center py-5 opacity-50">CARGANDO...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadVehiculos();
});

function loadVehiculos() {
    fetch('{{ url("api/vehiculos") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        const tbody = document.getElementById('vehiculosList');
        if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 opacity-50">NO HAY VEHÍCULOS REGISTRADOS</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(v => `
            <tr>
                <td><span class="badge bg-black text-white px-4 py-3 font-bold" style="letter-spacing:2px;font-family:monospace;border:2px solid #fff;">${v.placa_vehiculo}</span></td>
                <td class="uppercase font-bold">${v.tipo_vehiculo || '—'}</td>
                <td class="font-bold">${v.marca || '—'}</td>
                <td class="font-bold">${v.modelo || '—'}</td>
                <td class="font-bold">${v.tara_kg ? parseFloat(v.tara_kg).toFixed(0) + ' kg' : '—'}</td>
                <td><span class="badge font-bold uppercase px-3 py-2" style="background:${v.estado === 1 ? '#e2ffd6' : v.estado === 2 ? '#fffcd4' : '#ffdcd6'};color:${v.estado === 1 ? '#007400' : v.estado === 2 ? '#746700' : '#740000'};border:2px solid #000;">${v.estado === 1 ? 'ACTIVO' : v.estado === 2 ? 'MANTENIMIENTO' : 'VENDIDO'}</span></td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black font-bold" onclick="window.location.href='{{ url("vehiculos") }}/${v.id_vehiculo}/editar'" title="EDITAR"><i class="fas fa-edit"></i></button>
                        ${v.estado !== 3 ? `<button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarVehiculo(${v.id_vehiculo})" title="DAR DE BAJA"><i class="fas fa-trash"></i></button>` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    });
}

function eliminarVehiculo(id) {
    Swal.fire({
        title: 'DAR DE BAJA',
        text: '¿Está seguro de dar de baja este vehículo?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, BAJAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("vehiculos") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => {
                if (r.redirected) window.location.href = r.url;
                else loadVehiculos();
            });
        }
    });
}
</script>
@endpush