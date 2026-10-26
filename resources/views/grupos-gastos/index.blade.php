@extends('layouts.master')

@section('title', 'Grupos Gastos')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">GRUPOS GASTOS</h1>
            <p class="font-bold small text-black uppercase">Apartados del clasificador de gastos</p>
        </div>
        @can('administrar', 'clasificadores')
            <a href="{{ route('grupos-gastos.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
                <i class="fas fa-plus me-1"></i> NUEVO GRUPO
            </a>
        @endcan
    </header>

    <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span><i class="fas fa-folder-tree me-2"></i> Grupos de gastos registrados</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Total Clasificadores</th>
                        @can('administrar', 'clasificadores')
                            <th>Acciones</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($grupos as $grupo)
                        <tr>
                            <td class="font-bold"><span class="badge font-bold px-3 py-2" style="background:var(--success);color:#fff;border:2px solid var(--line);">{{ $grupo->nombre }}</span></td>
                            <td class="font-bold">{{ $grupo->descripcion ?: '—' }}</td>
                            <td class="font-bold">{{ $grupo->clasificadores }} clasificadores</td>
                            @can('administrar', 'clasificadores')
                                <td>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('grupos-gastos.edit', $grupo->id_grupo_gasto) }}"
                                           class="btn btn-sm btn-warning border-black font-bold" title="EDITAR">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger border-black font-bold" title="ELIMINAR"
                                                onclick="eliminarGrupoGasto({{ $grupo->id_grupo_gasto }}, @js($grupo->nombre))">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-5 opacity-50">NO HAY GRUPOS DE GASTOS REGISTRADOS</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function eliminarGrupoGasto(id, nombre) {
    Swal.fire({
        title: 'ELIMINAR GRUPO DE GASTOS',
        text: '¿Eliminar "' + nombre + '"? Solo se puede si no tiene clasificadores asociados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ELIMINAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: 'var(--danger)',
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch('{{ url('grupos-gastos') }}/' + id, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({ '_method': 'DELETE' })
        })
        .then(r => r.json().catch(() => ({})))
        .then(res => {
            if (res && res.success === false) {
                Swal.fire('Error', res.message || 'No se puede eliminar', 'error');
                return;
            }
            window.location.reload();
        })
        .catch(() => window.location.reload());
    });
}
</script>
@endpush
