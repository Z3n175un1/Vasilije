@extends('layouts.master')

@section('title', 'Almacén - VASILIJE')

@section('content')
<div class="main-container w-full">
    @if(session('success'))
        <div class="alert alert-success font-bold text-center mb-4" style="border:3px solid #000;border-radius:0;">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">ALMACÉN</h1>
            <p class="font-bold small text-black uppercase">Control de Inventario y Repuestos</p>
        </div>
        <a href="{{ route('almacen.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVO PRODUCTO
        </a>
    </header>

    <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span><i class="fas fa-warehouse me-2"></i> Inventario de Productos</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Unidad</th>
                        <th>Stock</th>
                        <th>Stock Mín.</th>
                        <th>Precio Compra</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="productosList">
                    <tr><td colspan="8" class="text-center py-5 opacity-50">CARGANDO...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', loadProductos);

function loadProductos() {
    fetch('{{ url("api/almacen") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        const tbody = document.getElementById('productosList');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 opacity-50">NO HAY PRODUCTOS REGISTRADOS</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(p => {
            const stockBajo = p.stock_actual <= p.stock_minimo;
            return `<tr>
                <td class="font-bold"><span class="badge bg-black text-white px-2">${p.codigo}</span></td>
                <td class="font-bold">${p.nombre_producto}</td>
                <td><span class="badge font-bold px-3 py-2" style="background:#ffc107;color:#000;border:2px solid #000;">${p.categoria}</span></td>
                <td class="font-bold">${p.unidad_medida}</td>
                <td class="font-bold" style="color:${stockBajo ? '#dc3545' : '#007400'};${stockBajo ? 'font-weight:900;' : ''}">${parseFloat(p.stock_actual || 0).toFixed(2)}</td>
                <td class="font-bold">${parseFloat(p.stock_minimo || 0).toFixed(2)}</td>
                <td class="font-bold">Bs. ${parseFloat(p.precio_compra || 0).toFixed(2)}</td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black font-bold" onclick="window.location.href='{{ url("almacen") }}/${p.id_inventario}/editar'" title="EDITAR"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarProducto(${p.id_inventario})" title="DESACTIVAR"><i class="fas fa-ban"></i></button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    });
}

function eliminarProducto(id) {
    Swal.fire({
        title: 'DESACTIVAR PRODUCTO',
        text: '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, DESACTIVAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("almacen") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => {
                if (r.redirected) window.location.href = r.url;
                else loadProductos();
            });
        }
    });
}
</script>
@endpush
