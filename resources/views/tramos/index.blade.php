@extends('layouts.master')

@section('title', 'Rutas - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">RUTAS</h1>
            <p class="font-bold small text-black uppercase">Gestión de Tramos y Recorridos</p>
        </div>
        <a href="{{ route('tramos.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVA RUTA
        </a>
    </header>

    <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span><i class="fas fa-route me-2"></i> Tramos Registrados</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Km</th>
                        <th>Precio Total</th>
                        <th>$/Ton</th>
                        <th>Gasolina</th>
                        <th>Diesel</th>
                        <th>Gas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tramosList">
                    <tr><td colspan="9" class="text-center py-5 opacity-50">CARGANDO...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', loadTramos);

function loadTramos() {
    fetch('{{ url("api/tramos") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        const tbody = document.getElementById('tramosList');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 opacity-50">NO HAY TRAMOS REGISTRADOS</td></tr>';
            return;
        }
            tbody.innerHTML = res.data.map(t => `
            <tr>
                <td class="font-bold">${t.origen}</td>
                <td class="font-bold">${t.destino}</td>
                <td class="font-bold">${parseFloat(t.kilometros || 0).toFixed(2)}</td>
                <td class="font-bold" style="color:#007400;">Bs. ${parseFloat(t.precio_total || 0).toFixed(2)}</td>
                <td class="font-bold">$${parseFloat(t.precio_dolar_tonelada || 0).toFixed(2)}</td>
                <td class="font-bold">Bs. ${parseFloat(t.gasolina_promedio || 0).toFixed(2)}</td>
                <td class="font-bold">Bs. ${parseFloat(t.diesel_promedio || 0).toFixed(2)}</td>
                <td class="font-bold">Bs. ${parseFloat(t.gas_promedio || 0).toFixed(2)}</td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black font-bold" onclick="window.location.href='{{ url("tramos") }}/${t.id_tramo}/editar'" title="EDITAR"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarTramo(${t.id_tramo})" title="ELIMINAR"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    });
}

function eliminarTramo(id) {
    Swal.fire({
        title: 'ELIMINAR TRAMO',
        text: '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ELIMINAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("tramos") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => {
                if (r.redirected) window.location.href = r.url;
                else loadTramos();
            });
        }
    });
}
</script>
@endpush
