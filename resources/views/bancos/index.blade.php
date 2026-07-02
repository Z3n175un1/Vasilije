@extends('layouts.master')

@section('title', 'Bancos - VASILIJE')

@section('content')
<div class="main-container w-full">
    @if(session('success'))
        <div class="alert alert-success font-bold text-center mb-4" style="border:3px solid #000;border-radius:0;">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">BANCOS</h1>
            <p class="font-bold small text-black uppercase">Control de Cuentas Bancarias</p>
        </div>
        <a href="{{ route('bancos.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVO BANCO
        </a>
    </header>

    <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span><i class="fas fa-university me-2"></i> Cuentas Bancarias</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0">
                <thead>
                    <tr>
                        <th>Banco</th>
                        <th>Cuenta</th>
                        <th>Titular</th>
                        <th>Tipo</th>
                        <th>Moneda</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="bancosList">
                    <tr><td colspan="8" class="text-center py-5 opacity-50">CARGANDO...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', loadBancos);

function loadBancos() {
    fetch('{{ url("api/bancos") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        const tbody = document.getElementById('bancosList');
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 opacity-50">NO HAY BANCOS REGISTRADOS</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(b => `
            <tr>
                <td class="font-bold">${b.nombre_banco}</td>
                <td class="font-bold">${b.numero_cuenta}</td>
                <td class="font-bold">${b.titular}</td>
                <td><span class="badge font-bold px-3 py-2" style="background:#ffc107;color:#000;border:2px solid #000;">${b.tipo_cuenta}</span></td>
                <td class="font-bold">${b.moneda}</td>
                <td class="font-bold" style="color:#007400;">Bs. ${parseFloat(b.saldo_actual || 0).toFixed(2)}</td>
                <td><span class="badge font-bold px-3 py-2" style="background:${b.estado === 'ACTIVO' ? '#e2ffd6' : '#ffdcd6'};color:${b.estado === 'ACTIVO' ? '#007400' : '#740000'};border:2px solid #000;">${b.estado}</span></td>
                <td>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black font-bold" onclick="window.location.href='{{ url("bancos") }}/${b.id_banco}/editar'" title="EDITAR"><i class="fas fa-edit"></i></button>
                        ${b.estado === 'ACTIVO' ? `<button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarBanco(${b.id_banco})" title="DESACTIVAR"><i class="fas fa-ban"></i></button>` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    });
}

function eliminarBanco(id) {
    Swal.fire({
        title: 'DESACTIVAR BANCO',
        text: '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, DESACTIVAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("bancos") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => {
                if (r.redirected) window.location.href = r.url;
                else loadBancos();
            });
        }
    });
}
</script>
@endpush
