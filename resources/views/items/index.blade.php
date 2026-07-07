@extends('layouts.master')

@section('title', 'Ítems - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">ÍTEMS</h1>
            <p class="font-bold small text-black uppercase">Catálogo de Productos y Servicios</p>
        </div>
        <a href="{{ route('items.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVO ÍTEM
        </a>
    </header>

    <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span><i class="fas fa-box me-2"></i> Catálogo de Ítems</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Grupo</th>
                        <th>Unidad</th>
                        <th>Stock Mín.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="itemsList">
                    <tr><td colspan="6" class="text-center py-5 opacity-50">CARGANDO...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', loadItems);

function loadItems() {
    fetch('{{ url("api/items") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        const tbody = document.getElementById('itemsList');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 opacity-50">NO HAY ÍTEMS REGISTRADOS</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(p => `
            <tr>
                <td class="font-bold"><span class="badge bg-black text-white px-2">${p.codigo}</span></td>
                <td class="font-bold">${p.nombre_producto}</td>
                <td><span class="badge font-bold px-3 py-2" style="background:#ffc107;color:#000;border:2px solid #000;">${p.categoria}</span></td>
                <td class="font-bold">${p.unidad_medida}</td>
                <td class="font-bold" style="color:#007400;">${parseFloat(p.stock_minimo || 0).toFixed(2)}</td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black font-bold" onclick="window.location.href='{{ url("items") }}/${p.id_inventario}/editar'" title="EDITAR"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarItem(${p.id_inventario})" title="DESACTIVAR"><i class="fas fa-ban"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    });
}

function eliminarItem(id) {
    Swal.fire({
        title: 'DESACTIVAR ÍTEM',
        text: '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, DESACTIVAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("items") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => {
                if (r.redirected) window.location.href = r.url;
                else loadItems();
            });
        }
    });
}
</script>
@endpush
