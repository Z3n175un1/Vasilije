@extends('layouts.master')

@section('title', 'Proveedores')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">PROVEEDORES</h1>
            <p class="font-bold small text-black uppercase">Control de Proveedores</p>
        </div>
        <a href="{{ route('proveedores.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVO PROVEEDOR
        </a>
    </header>

    <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <span><i class="fas fa-handshake me-2"></i> Proveedores Registrados</span>
            <div class="d-flex align-items-center gap-2" style="max-width:460px;width:100%;">
                <input type="text" id="buscarProveedor" class="form-control fw-bold" style="border-radius:0;border:3px solid #000;padding:8px 12px;" placeholder="BUSCAR POR NOMBRE, NIT, CONTACTO..." oninput="loadProveedores()">
                <select id="ordenProveedor" class="form-control fw-bold" style="border-radius:0;border:3px solid #000;padding:8px 12px;width:auto;" onchange="loadProveedores()">
                    <option value="abc">A-Z</option>
                    <option value="z_a">Z-A</option>
                    <option value="ultimo">ÚLTIMOS</option>
                </select>
                <button class="btn fw-bold" style="background:#000;color:#fff;border:3px solid #000;border-radius:0;" onclick="loadProveedores()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>Razón Social</th>
                        <th>NIT</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="proveedoresList">
                    <tr><td colspan="7" class="text-center py-5 opacity-50">CARGANDO...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', loadProveedores);

function loadProveedores() {
    const busqueda = document.getElementById('buscarProveedor').value;
    const orden = document.getElementById('ordenProveedor').value;
    const params = new URLSearchParams();
    if (busqueda) params.append('busqueda', busqueda);
    params.append('orden', orden);

    fetch('{{ url("api/proveedores") }}' + (params.toString() ? '?' + params.toString() : ''), {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        const tbody = document.getElementById('proveedoresList');
        if (!res.success || !res.data) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 opacity-50 text-danger">ERROR AL CARGAR DATOS</td></tr>';
            return;
        }
        if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 opacity-50">NO HAY PROVEEDORES REGISTRADOS</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(p => `
            <tr>
                <td class="font-bold">${p.nombre_proveedor}</td>
                <td class="font-bold">${p.nit_ci || '—'}</td>
                <td>${p.contacto || '—'}</td>
                <td class="font-bold">${p.telefono || '—'}</td>
                <td>${p.email || '—'}</td>
                <td><span class="badge font-bold px-3 py-2" style="background:#2f2c79;color:#fff;border:2px solid #000;">${p.tipo_proveedor || 'GENERAL'}</span></td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black font-bold" onclick="window.location.href='{{ url("proveedores") }}/${p.id_proveedor}/editar'" title="EDITAR"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarProveedor(${p.id_proveedor})" title="ELIMINAR"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    })
    .catch(() => {
        document.getElementById('proveedoresList').innerHTML = '<tr><td colspan="7" class="text-center py-5 opacity-50 text-danger">ERROR DE CONEXIÓN</td></tr>';
    });
}

function eliminarProveedor(id) {
    Swal.fire({
        title: 'ELIMINAR PROVEEDOR',
        text: '¿Está seguro? Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ELIMINAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("proveedores") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => {
                if (r.redirected) window.location.href = r.url;
                else loadProveedores();
            });
        }
    });
}
</script>
@endpush
