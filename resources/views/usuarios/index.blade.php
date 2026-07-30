@extends('layouts.master')

@section('title', 'Usuarios')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">USUARIOS</h1>
            <p class="font-bold small text-black uppercase">Administración del Sistema</p>
        </div>
        <a href="{{ route('usuarios.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVO USUARIO
        </a>
    </header>

    <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users me-2"></i> Usuarios Registrados</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="usuariosList">
                    <tr><td colspan="6" class="text-center py-5 opacity-50">CARGANDO...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', loadUsuarios);

function loadUsuarios() {
    const tbody = document.getElementById('usuariosList');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-dark" role="status"></div></td></tr>';

    fetch('/api/usuarios', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success || !res.data) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 opacity-50 text-danger">ERROR AL CARGAR DATOS</td></tr>';
            return;
        }
        if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 opacity-50">NO HAY USUARIOS REGISTRADOS</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(u => `
            <tr>
                <td class="font-bold">${u.usuario}</td>
                <td class="font-bold">${(u.nombres || '') + ' ' + (u.apellidos || '')}</td>
                <td class="font-bold">${u.email || '—'}</td>
                <td><span class="badge font-bold px-3 py-2" style="background:${rolColor(u.rol)};color:#000;border:2px solid #000;">${u.rol.toUpperCase()}</span></td>
                <td>${u.estado == 1 ? '<span class="badge bg-success font-bold px-3 py-2 border-black">ACTIVO</span>' : '<span class="badge bg-secondary font-bold px-3 py-2 border-black">INACTIVO</span>'}</td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black font-bold" onclick="window.location.href='{{ url("usuarios") }}/${u.id_usuario}/editar'" title="EDITAR"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarUsuario(${u.id_usuario}, '${u.usuario}')" title="DESACTIVAR"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    })
    .catch(() => {
        document.getElementById('usuariosList').innerHTML = '<tr><td colspan="6" class="text-center py-5 opacity-50 text-danger">ERROR DE CONEXIÓN</td></tr>';
    });
}

function rolColor(rol) {
    const colors = { admin: '#dc3545', supervisor: '#2f2c79', operador: '#0d6efd', lectura: '#198754' };
    return colors[rol] || '#6c757d';
}

function eliminarUsuario(id, usuario) {
    Swal.fire({
        title: 'DESACTIVAR USUARIO',
        text: 'Se desactivará a ' + usuario + '. Continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, DESACTIVAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("usuarios") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => r.json().catch(() => ({})))
            .then(res => {
                if (res.success) {
                    Swal.fire('Desactivado', '', 'success');
                } else {
                    Swal.fire('Error', res.message || 'No se pudo desactivar', 'error');
                }
                loadUsuarios();
            }).catch(() => window.location.reload());
        }
    });
}
</script>
@endpush
