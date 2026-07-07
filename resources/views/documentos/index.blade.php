@extends('layouts.master')

@section('title', 'Inicio - VASILIJE')

@push('styles')
<style>
.btn-inicio {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 20px;
    border: 4px solid #000;
    background: #fff;
    text-decoration: none;
    color: #000;
    font-weight: 800;
    font-size: 1.1rem;
    transition: all 0.15s;
    min-height: 160px;
}
.btn-inicio i {
    font-size: 3rem;
    margin-bottom: 12px;
}
.btn-inicio:hover {
    background: #ffc107 !important;
    color: #000 !important;
    transform: scale(1.02);
}
.nomen-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.nomen-popup {
    background: #fff;
    border: 5px solid #000;
    max-width: 400px;
    width: 90%;
    box-shadow: 10px 10px 0 rgba(0,0,0,0.3);
}
.nomen-popup-header {
    background: #000;
    color: #ffc107;
    padding: 16px 20px;
    font-weight: 800;
    font-size: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.nomen-popup-body {
    padding: 20px;
}
.nomen-opt {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border: 3px solid #000;
    background: #fff;
    color: #000;
    font-weight: 700;
    font-size: 0.95rem;
    transition: all 0.15s;
    cursor: pointer;
    width: 100%;
    text-align: left;
}
.nomen-opt:hover {
    background: #ffc107 !important;
    color: #000 !important;
}
#nomenTableArea {
    display: none;
    margin-top: 32px;
}
</style>
@endpush

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">BIENVENIDO, {{ auth()->user()->name }}</h1>
            <p class="font-bold small text-black uppercase">Panel Principal - Seleccione un módulo</p>
        </div>
    </header>

    <div class="row g-4">
        <div class="col-md-4">
            <a href="{{ route('dashboard.index') }}" class="btn-inicio w-100">
                <i class="fas fa-truck"></i>
                <span>UNIDADES</span>
            </a>
        </div>

        <div class="col-md-4">
            <div class="btn-inicio w-100" onclick="abrirNomen()" style="cursor:pointer;user-select:none;">
                <i class="fas fa-folder-tree"></i>
                <span>NOMENCLATURAS</span>
            </div>
        </div>

        <div class="col-md-4">
            <a href="{{ route('facturacion.index') }}" class="btn-inicio w-100">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>REGISTRO DE FLETES (INGRESO)</span>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ route('gastos.index') }}" class="btn-inicio w-100">
                <i class="fas fa-minus-circle"></i>
                <span>REGISTRO DE GASTOS</span>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ route('almacen.index') }}" class="btn-inicio w-100">
                <i class="fas fa-warehouse"></i>
                <span>ALMACENES</span>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ route('reportes.index') }}" class="btn-inicio w-100">
                <i class="fas fa-chart-bar"></i>
                <span>REPORTES</span>
            </a>
        </div>
    </div>

    <div id="nomenTableArea" style="display:none;">
        <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
            <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
                <span id="nomenTableTitle"><i class="fas fa-table me-2"></i> NOMENCLATURAS</span>
                <button class="btn btn-sm fw-bold" style="background:#000;color:#ffc107;border:2px solid #000;padding:4px 12px;" onclick="abrirNomen()">CAMBIAR</button>
            </div>
            <div class="table-responsive-brutalist">
                <table class="table-excel mb-0" style="font-size:.85rem;">
                    <thead id="nomenTableHead"><tr><td class="text-center py-4 fw-bold">SELECCIONE UNA OPCIÓN EN NOMENCLATURAS</td></tr></thead>
                    <tbody id="nomenTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

<div class="nomen-overlay" id="nomenOverlay" onclick="if(event.target===this)cerrarNomen()">
    <div class="nomen-popup">
        <div class="nomen-popup-header">
            <span><i class="fas fa-folder-tree me-2"></i> NOMENCLATURAS</span>
            <button onclick="cerrarNomen()" style="background:none;border:none;color:#ffc107;font-size:1.5rem;font-weight:800;cursor:pointer;">×</button>
        </div>
        <div class="nomen-popup-body">
            <p class="fw-bold mb-3 text-center" style="font-size:1.1rem;">¿Qué desea ver?</p>
            <div class="d-flex flex-column gap-2">
                <div class="nomen-opt" onclick="cargarNomen('personal')"><i class="fas fa-users"></i> PERSONAL</div>
                <div class="nomen-opt" onclick="cargarNomen('grupos')"><i class="fas fa-layer-group"></i> GRUPOS</div>
                <div class="nomen-opt" onclick="cargarNomen('items')"><i class="fas fa-box"></i> ÍTEMS</div>
                <div class="nomen-opt" onclick="cargarNomen('tramos')"><i class="fas fa-route"></i> RUTAS</div>
                <div class="nomen-opt" onclick="cargarNomen('bancos')"><i class="fas fa-university"></i> BANCOS</div>
                <div class="nomen-opt" onclick="cargarNomen('config')"><i class="fas fa-cogs"></i> CONFIGURACIÓN</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function abrirNomen() { document.getElementById('nomenOverlay').style.display = 'flex'; }
function cerrarNomen() { document.getElementById('nomenOverlay').style.display = 'none'; }

function cargarNomen(tabla) {
    cerrarNomen();
    document.getElementById('nomenTableArea').style.display = 'block';
    const head = document.getElementById('nomenTableHead');
    const body = document.getElementById('nomenTableBody');

    const endpoints = {
        personal: { url: '{{ url("api/personal") }}', title: 'PERSONAL' },
        grupos: { url: '{{ url("api/grupos") }}', title: 'GRUPOS' },
        items: { url: '{{ url("api/items") }}', title: 'ÍTEMS' },
        tramos: { url: '{{ url("api/tramos") }}', title: 'RUTAS' },
        bancos: { url: '{{ url("api/bancos") }}', title: 'BANCOS' },
        config: { url: '{{ url("api/config") }}', title: 'CONFIGURACIÓN' },
    };
    const ep = endpoints[tabla];
    if (!ep) return;
    document.getElementById('nomenTableTitle').innerHTML = '<i class="fas fa-table me-2"></i> ' + ep.title;

    const renderers = {
        personal: (data) => {
            head.innerHTML = '<tr><th>NOMBRES</th><th>APELLIDOS</th><th>CI</th><th>TELÉFONO</th><th>ESTADO</th></tr>';
            if (!data || data.length === 0) { body.innerHTML = '<tr><td colspan="5" class="text-center py-4 opacity-50">SIN REGISTROS</td></tr>'; return; }
            body.innerHTML = data.map(r => `<tr><td class="fw-bold">${r.nombres || '—'}</td><td class="fw-bold">${r.apellidos || '—'}</td><td class="fw-bold">${r.ci || '—'}</td><td class="fw-bold">${r.telefono || r.celular || '—'}</td><td class="fw-bold">${r.estado == 1 ? 'ACTIVO' : 'INACTIVO'}</td></tr>`).join('');
        },
        grupos: (data) => {
            head.innerHTML = '<tr><th>NOMBRE</th><th>DESCRIPCIÓN</th><th>TOTAL PRODUCTOS</th></tr>';
            if (!data || data.length === 0) { body.innerHTML = '<tr><td colspan="3" class="text-center py-4 opacity-50">SIN REGISTROS</td></tr>'; return; }
            body.innerHTML = data.map(r => `<tr><td class="fw-bold">${r.nombre || '—'}</td><td class="fw-bold">${r.descripcion || '—'}</td><td class="fw-bold">${r.total_productos || 0}</td></tr>`).join('');
        },
        items: (data) => {
            head.innerHTML = '<tr><th>CÓDIGO</th><th>NOMBRE</th><th>GRUPO</th><th>UNIDAD</th><th>STOCK MÍN</th></tr>';
            if (!data || data.length === 0) { body.innerHTML = '<tr><td colspan="5" class="text-center py-4 opacity-50">SIN REGISTROS</td></tr>'; return; }
            body.innerHTML = data.map(r => `<tr><td class="fw-bold">${r.codigo || '—'}</td><td class="fw-bold">${r.nombre_producto || '—'}</td><td class="fw-bold">${r.categoria || '—'}</td><td class="fw-bold">${r.unidad_medida || '—'}</td><td class="fw-bold">${parseFloat(r.stock_minimo || 0).toFixed(2)}</td></tr>`).join('');
        },
        tramos: (data) => {
            head.innerHTML = '<tr><th>ORIGEN</th><th>DESTINO</th><th>DISTANCIA</th><th>PRECIO</th><th>$/TON</th></tr>';
            if (!data || data.length === 0) { body.innerHTML = '<tr><td colspan="5" class="text-center py-4 opacity-50">SIN REGISTROS</td></tr>'; return; }
            body.innerHTML = data.map(r => `<tr><td class="fw-bold">${r.origen || '—'}</td><td class="fw-bold">${r.destino || '—'}</td><td class="fw-bold">${r.distancia_km || '—'}</td><td class="fw-bold">Bs. ${parseFloat(r.precio_total || 0).toFixed(2)}</td><td class="fw-bold">$${parseFloat(r.precio_dolar_tonelada || 0).toFixed(2)}</td></tr>`).join('');
        },
        bancos: (data) => {
            head.innerHTML = '<tr><th>BANCO</th><th>TIPO</th><th>N° CUENTA</th><th>TITULAR</th><th>SALDO</th></tr>';
            if (!data || data.length === 0) { body.innerHTML = '<tr><td colspan="5" class="text-center py-4 opacity-50">SIN REGISTROS</td></tr>'; return; }
            body.innerHTML = data.map(r => `<tr><td class="fw-bold">${r.nombre_banco || '—'}</td><td class="fw-bold">${r.tipo_cuenta || '—'}</td><td class="fw-bold">${r.numero_cuenta || '—'}</td><td class="fw-bold">${r.titular || '—'}</td><td class="fw-bold">Bs. ${parseFloat(r.saldo_actual || 0).toFixed(2)}</td></tr>`).join('');
        },
        config: (data) => {
            head.innerHTML = '<tr><th>CONFIGURACIÓN</th><th>VALOR</th></tr>';
            if (!data || !data.data) { body.innerHTML = '<tr><td colspan="2" class="text-center py-4 opacity-50">SIN DATOS</td></tr>'; return; }
            const labels = { tipo_cambio: 'Tipo de Cambio (Bs/$)', precio_tonelada_usd: 'Precio Tonelada (USD)' };
            body.innerHTML = Object.entries(data.data).map(([k, v]) =>
                `<tr><td class="fw-bold">${labels[k] || k}</td><td class="fw-bold">${v}</td></tr>`
            ).join('');
        }
    };

    const fn = renderers[tabla];
    if (!fn) return;

    head.innerHTML = '<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>CARGANDO...</td></tr>';
    body.innerHTML = '';

    fetch(ep.url, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(res => { fn(res.data || []); })
        .catch(() => { head.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger fw-bold">ERROR AL CARGAR</td></tr>'; });
}
</script>
@endpush
@endsection
