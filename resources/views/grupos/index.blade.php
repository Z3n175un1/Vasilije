@extends('layouts.master')

@section('title', 'Grupos - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">GRUPOS</h1>
            <p class="font-bold small text-black uppercase">Categorías y Clasificaciones</p>
        </div>
        <a href="{{ route('grupos.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVO GRUPO
        </a>
    </header>

    <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span><i class="fas fa-layer-group me-2"></i> Categorías Registradas</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Total Productos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="gruposList">
                    <tr><td colspan="4" class="text-center py-5 opacity-50">CARGANDO...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', loadGrupos);

function loadGrupos() {
    fetch('{{ url("api/grupos") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        const tbody = document.getElementById('gruposList');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 opacity-50">NO HAY GRUPOS REGISTRADOS</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(g => `
            <tr>
                <td class="font-bold"><span class="badge font-bold px-3 py-2" style="background:#ffc107;color:#000;border:2px solid #000;">${g.nombre}</span></td>
                <td class="font-bold">${g.descripcion || '—'}</td>
                <td class="font-bold">${g.total_productos || 0} productos</td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black font-bold" onclick="window.location.href='{{ url("grupos") }}/${g.id_categoria}/editar'" title="EDITAR"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarGrupo(${g.id_categoria})" title="ELIMINAR"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    });
}

function eliminarGrupo(id) {
    Swal.fire({
        title: 'ELIMINAR GRUPO',
        text: '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ELIMINAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("grupos") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => r.json().catch(() => {}))
            .then(res => {
                if (res && !res.success) {
                    Swal.fire('Error', res.message || 'No se puede eliminar', 'error');
                }
                loadGrupos();
            }).catch(() => window.location.reload());
        }
    });
}
</script>
@endpush
