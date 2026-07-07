@extends('layouts.master')

@section('title', 'Personal - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">PERSONAL</h1>
            <p class="font-bold small text-black uppercase">Gestión de Conductores y Empleados</p>
        </div>
        <a href="{{ route('personal.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVO PERSONAL
        </a>
    </header>
    <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users me-2"></i> Lista de Personal</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>CI</th>
                        <th>Cargo</th>
                        <th>Teléfono</th>
                        <th>Sueldo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="personalList">
                    <tr><td colspan="7" class="text-center py-5 opacity-50">CARGANDO...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', loadPersonal);

function loadPersonal() {
    fetch('{{ url("api/personal") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        const tbody = document.getElementById('personalList');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 opacity-50">NO HAY PERSONAL REGISTRADO</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(p => `
            <tr>
                <td class="font-bold text-start ps-3">${p.nombres || ''} ${p.apellidos || ''}</td>
                <td class="font-bold">${p.ci || '—'}</td>
                <td><span class="badge font-bold px-3 py-2" style="background:#ffc107;color:#000;border:2px solid #000;">${p.cargo || '—'}</span></td>
                <td class="font-bold">${p.telefono || '—'}</td>
                <td class="font-bold" style="color:#007400;">Bs. ${parseFloat(p.sueldo || 0).toFixed(2)}</td>
                <td><span class="badge font-bold px-3 py-2" style="background:${p.estado == 1 ? '#e2ffd6' : '#ffdcd6'};color:${p.estado == 1 ? '#007400' : '#740000'};border:2px solid #000;">${p.estado == 1 ? 'ACTIVO' : 'INACTIVO'}</span></td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black font-bold" onclick="window.location.href='{{ url("personal") }}/${p.id_personal}/editar'" title="EDITAR"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarPersonal(${p.id_personal})" title="ELIMINAR"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    });
}

function eliminarPersonal(id) {
    Swal.fire({
        title: 'ELIMINAR PERSONAL',
        text: '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ELIMINAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("personal") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => {
                if (r.redirected) window.location.href = r.url;
                else loadPersonal();
            });
        }
    });
}
</script>
@endpush