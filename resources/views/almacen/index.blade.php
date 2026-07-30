@extends('layouts.master')

@section('title', 'Almacén')

@push('styles')
<style>
.modal-overlay-fact {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.modal-content-fact {
    max-width: 560px;
    width: 95%;
    max-height: 90vh;
    overflow-y: auto;
}
.tab-btn-alm {
    border: none;
    font-weight: 800;
    padding: 12px 16px;
    font-size: 0.85rem;
    flex: 1;
    transition: all 0.2s;
}
.tab-btn-alm.active {
    background: #000 !important;
    color: #ffc107 !important;
}
.tab-btn-alm:not(.active) {
    background: #fff;
    color: #000;
    border-right: 3px solid #000;
}
.tab-btn-alm:not(.active):last-child { border-right: none; }
</style>
@endpush

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">ALMACÉN</h1>
            <p class="font-bold small text-black uppercase">Control de Inventario y Movimientos</p>
        </div>
        <a href="{{ route('items.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-box me-1"></i> NUEVO ÍTEM
        </a>
    </header>

    <div class="d-flex mb-4" style="border:3px solid #000;">
        <button class="tab-btn-alm active" onclick="switchTabAlm('inventario',this)"><i class="fas fa-warehouse me-2"></i> INVENTARIO</button>
        <button class="tab-btn-alm" onclick="switchTabAlm('compras',this)"><i class="fas fa-arrow-down me-2"></i> COMPRAS</button>
        <button class="tab-btn-alm" onclick="switchTabAlm('entregas',this)"><i class="fas fa-arrow-up me-2"></i> ENTREGAS</button>
        <button class="tab-btn-alm" onclick="switchTabAlm('kardex',this)"><i class="fas fa-book me-2"></i> KARDEX</button>
        <button class="tab-btn-alm" onclick="switchTabAlm('saldos',this)"><i class="fas fa-balance-scale me-2"></i> SALDOS</button>
    </div>

    <div id="tabInventario">
        <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
            <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
                <span><i class="fas fa-warehouse me-2"></i> Inventario de Productos</span>
            </div>
            <div class="table-responsive-brutalist">
                <table class="table-excel mb-0" style="font-size:.85rem;">
                    <thead><tr><th>Código</th><th>Producto</th><th>Cat.</th><th>Unidad</th><th>Stock</th><th>Mín.</th><th>Compra</th><th>Lote</th><th>Acciones</th></tr></thead>
                    <tbody id="productosList"><tr><td colspan="9" class="text-center py-5 opacity-50">CARGANDO...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tabCompras" style="display:none;">
        <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
            <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
                <span><i class="fas fa-arrow-down me-2"></i> Compras (Ingresos a Almacén)</span>
                <button class="btn btn-sm fw-bold" style="background:#000;color:#ffc107;border:3px solid #000;padding:8px 16px;" onclick="abrirModalMovimiento('COMPRA')"><i class="fas fa-plus me-1"></i> NUEVA COMPRA</button>
            </div>
                <div class="table-responsive-brutalist">
                    <table class="table-excel mb-0" style="font-size:.85rem;">
                        <thead><tr><th>Fecha</th><th>Producto</th><th>Cantidad</th><th>P. Unit.</th><th>Proveedor</th><th>Lote</th></tr></thead>
                        <tbody id="comprasList"><tr><td colspan="6" class="text-center py-5 opacity-50">CARGANDO...</td></tr></tbody>
                    </table>
                </div>
        </div>
    </div>

    <div id="tabEntregas" style="display:none;">
        <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
            <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
                <span><i class="fas fa-arrow-up me-2"></i> Entregas (Salidas de Almacén)</span>
                <button class="btn btn-sm fw-bold" style="background:#000;color:#ffc107;border:3px solid #000;padding:8px 16px;" onclick="abrirModalMovimiento('ENTREGA')"><i class="fas fa-plus me-1"></i> NUEVA ENTREGA</button>
            </div>
                <div class="table-responsive-brutalist">
                    <table class="table-excel mb-0" style="font-size:.85rem;">
                        <thead><tr><th>Fecha</th><th>Producto</th><th>Cantidad</th><th>P. Unit.</th><th>Vehículo</th><th>Conductor</th></tr></thead>
                        <tbody id="entregasList"><tr><td colspan="6" class="text-center py-5 opacity-50">CARGANDO...</td></tr></tbody>
                    </table>
                </div>
        </div>
    </div>

    <div id="tabKardex" style="display:none;">
        <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
            <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
                <span><i class="fas fa-book me-2"></i> Kardex por Producto</span>
            </div>
            <div class="p-3">
                <select class="form-control fw-bold mb-3" id="kardexProducto" style="border-radius:0;border:3px solid #000;padding:10px;max-width:400px;" onchange="cargarKardex()">
                    <option value="">SELECCIONE PRODUCTO...</option>
                </select>
            </div>
            <div class="table-responsive-brutalist">
                <table class="table-excel mb-0" style="font-size:.85rem;">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th>Saldo</th><th>Detalle</th></tr></thead>
                    <tbody id="kardexList"><tr><td colspan="6" class="text-center py-5 opacity-50">SELECCIONE UN PRODUCTO</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tabSaldos" style="display:none;">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="p-3 text-center" style="background:#d4edda;border:3px solid #000;">
                    <div class="small fw-bold">Total Productos</div>
                    <div class="fs-5 fw-bold" id="saldoTotalProductos">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 text-center" style="background:#fff3cd;border:3px solid #000;">
                    <div class="small fw-bold">Stock Bajo</div>
                    <div class="fs-5 fw-bold" style="color:#dc3545;" id="saldoStockBajo">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 text-center" style="background:#cce5ff;border:3px solid #000;">
                    <div class="small fw-bold">Valor Inventario</div>
                    <div class="fs-5 fw-bold" id="saldoValor">Bs. 0</div>
                </div>
            </div>
        </div>
        <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
            <div class="table-responsive-brutalist">
                <table class="table-excel mb-0" style="font-size:.85rem;">
                    <thead><tr><th>Producto</th><th>Stock</th><th>Mín.</th><th>Estado</th></tr></thead>
                    <tbody id="saldosList"><tr><td colspan="4" class="text-center py-5 opacity-50">CARGANDO...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay-fact" id="modalMovimiento" style="display:none;z-index:9999;" onclick="if(event.target===this)cerrarModalMov()">
    <div class="modal-content-fact" onclick="event.stopPropagation()">
        <div class="p-3" style="background:#000;color:#ffc107;">
            <h3 class="mb-0 fw-bold fs-5" id="modalMovTitle"><i class="fas fa-exchange-alt me-2"></i> REGISTRAR MOVIMIENTO</h3>
        </div>
        <div class="p-3" style="background:#fff;border:4px solid #000;border-top:none;">
            <form id="formMovimiento" onsubmit="return guardarMovimiento(event)">
                @csrf
                <input type="hidden" name="tipo_movimiento" id="movTipo">
                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <label class="fw-bold small text-uppercase">FECHA <span class="text-danger">*</span></label>
                        <input type="date" class="form-control fw-bold" id="movFecha" style="border-radius:0;border:3px solid #000;padding:10px;">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="fw-bold small text-uppercase">PRODUCTO <span class="text-danger">*</span></label>
                        <select class="form-control fw-bold" id="movIdProducto" style="border-radius:0;border:3px solid #000;padding:10px;" required onchange="mostrarStockProducto()">
                            <option value="">SELECCIONE...</option>
                        </select>
                        <div id="movStockInfo" class="mt-2 p-2 fw-bold text-center" style="border:3px solid #000;display:none;"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small text-uppercase">CÓDIGO DE LOTE</label>
                        <input type="text" class="form-control fw-bold" id="movCodigoLote" style="border-radius:0;border:3px solid #000;padding:10px;background:#f0f0f0;font-family:monospace;letter-spacing:1px;" readonly placeholder="LO-000001">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="fw-bold small text-uppercase">CANTIDAD <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="movCantidad" style="border-radius:0;border:3px solid #000;padding:10px;" required min="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small text-uppercase">PRECIO UNITARIO (Bs)</label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="movPrecioUnitario" style="border-radius:0;border:3px solid #000;padding:10px;" min="0" placeholder="0.00">
                    </div>
                    <div class="col-md-4" id="movPrecioCompraRow">
                        <label class="fw-bold small text-uppercase">PRECIO COMPRA (Bs)</label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="movPrecioCompra" style="border-radius:0;border:3px solid #000;padding:10px;" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="row g-3 mb-3" id="movProveedorRow">
                    <div class="col-12">
                        <label class="fw-bold small text-uppercase">PROVEEDOR</label>
                        <input type="text" class="form-control fw-bold" id="movProveedor" style="border-radius:0;border:3px solid #000;padding:10px;" placeholder="Nombre del proveedor">
                    </div>
                </div>
                <div class="row g-3 mb-3" id="movVehiculoRow" style="display:none;">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">VEHÍCULO</label>
                        <select class="form-control fw-bold" id="movIdVehiculo" style="border-radius:0;border:3px solid #000;padding:10px;" onchange="autoAsignarConductor()">
                            <option value="">SELECCIONE...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">CONDUCTOR</label>
                        <select class="form-control fw-bold" id="movIdPersonal" style="border-radius:0;border:3px solid #000;padding:10px;">
                            <option value="">SELECCIONE...</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="fw-bold small text-uppercase">OBSERVACIONES</label>
                        <textarea class="form-control fw-bold" id="movObs" rows="2" style="border-radius:0;border:3px solid #000;padding:10px;"></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn fw-bold flex-grow-1" style="background:#000;color:#ffc107;border:4px solid #000;padding:12px;" id="btnGuardarMov">
                        <i class="fas fa-save"></i> GUARDAR
                    </button>
                    <button type="button" class="btn fw-bold" style="border:4px solid #000;padding:12px;" onclick="cerrarModalMov()">CANCELAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let productosInv = [];
let vehiculosInv = [];
let personalInv = [];

document.addEventListener('DOMContentLoaded', function() {
    loadProductos();
    loadCompras();
    loadEntregas();
    loadSaldos();
    fetch('{{ url("api/vehiculos") }}?estado=1', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(res => { if (res.success) vehiculosInv = res.data || []; });
    fetch('{{ url("api/personal") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(res => { if (res.success) personalInv = res.data || []; });
    fetch('{{ url("api/almacen") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(res => {
            if (res.success) {
                productosInv = res.data || [];
                const selP = document.getElementById('movIdProducto');
                selP.innerHTML = '<option value="">SELECCIONE...</option>' + (res.data || []).map(p =>
                    `<option value="${p.id_inventario}">${p.codigo} - ${p.nombre_producto}</option>`).join('');
                const selK = document.getElementById('kardexProducto');
                selK.innerHTML = '<option value="">SELECCIONE PRODUCTO...</option>' + (res.data || []).map(p =>
                    `<option value="${p.id_inventario}">${p.codigo} - ${p.nombre_producto}</option>`).join('');
            }
        });
});

function switchTabAlm(tab, btn) {
    document.querySelectorAll('.tab-btn-alm').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    ['tabInventario','tabCompras','tabEntregas','tabKardex','tabSaldos'].forEach(id => {
        document.getElementById(id).style.display = id === 'tab' + tab.charAt(0).toUpperCase() + tab.slice(1) ? 'block' : 'none';
    });
}

function loadProductos() {
    fetch('{{ url("api/almacen") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(res => {
            const tbody = document.getElementById('productosList');
            if (!res.success || !res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 opacity-50">NO HAY PRODUCTOS</td></tr>'; return;
            }
            tbody.innerHTML = res.data.map(p => {
                const sb = p.stock_actual <= p.stock_minimo;
                return `<tr>
                    <td class="font-bold"><span class="badge bg-black text-white px-2">${p.codigo}</span></td>
                    <td class="font-bold">${p.nombre_producto}</td>
                    <td><span class="badge font-bold px-2 py-1" style="background:#ffc107;color:#000;border:2px solid #000;">${p.categoria}</span></td>
                    <td class="font-bold">${p.unidad_medida}</td>
                    <td class="font-bold" style="color:${sb ? '#dc3545' : '#007400'};">${parseFloat(p.stock_actual || 0).toFixed(2)}</td>
                    <td class="font-bold">${parseFloat(p.stock_minimo || 0).toFixed(2)}</td>
                    <td class="font-bold">Bs. ${parseFloat(p.precio_compra || 0).toFixed(2)}</td>
                    <td class="font-bold"><span class="badge bg-black text-white px-2" style="font-family:monospace;">${p.codigo_lote || '—'}</span></td>
                    <td>
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarProducto(${p.id_inventario})"><i class="fas fa-ban"></i></button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        });
}

function loadCompras() {
    fetch('{{ url("api/almacen/movimientos") }}?tipo=COMPRA', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(res => {
            const tbody = document.getElementById('comprasList');
            if (!res.success || !res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 opacity-50">SIN MOVIMIENTOS</td></tr>'; return;
            }
            tbody.innerHTML = res.data.filter(m => m.tipo_movimiento === 'COMPRA').map(m =>
                `<tr><td class="fw-bold">${m.fecha_movimiento}</td><td class="fw-bold">${m.nombre_producto || '—'}</td><td class="fw-bold">${parseFloat(m.cantidad || 0).toFixed(2)}</td><td class="fw-bold">Bs. ${parseFloat(m.costo_unitario || 0).toFixed(2)}</td><td class="fw-bold">${m.proveedor || '—'}</td><td class="fw-bold"><span class="badge bg-black text-white px-2" style="font-family:monospace;">${m.codigo_lote || '—'}</span></td></tr>`
            ).join('') || '<tr><td colspan="6" class="text-center py-5 opacity-50">SIN COMPRAS REGISTRADAS</td></tr>';
        });
}

function loadEntregas() {
    fetch('{{ url("api/almacen/movimientos") }}?tipo=SALIDA', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(res => {
            const tbody = document.getElementById('entregasList');
            if (!res.success || !res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 opacity-50">SIN MOVIMIENTOS</td></tr>'; return;
            }
            tbody.innerHTML = res.data.filter(m => m.tipo_movimiento === 'SALIDA').map(m =>
                `<tr><td class="fw-bold">${m.fecha_movimiento}</td><td class="fw-bold">${m.nombre_producto || '—'}</td><td class="fw-bold">${parseFloat(m.cantidad || 0).toFixed(2)}</td><td class="fw-bold">Bs. ${parseFloat(m.costo_unitario || 0).toFixed(2)}</td><td class="fw-bold">${m.placa_vehiculo || '—'}</td><td class="fw-bold">${m.conductor || '—'}</td></tr>`
            ).join('') || '<tr><td colspan="6" class="text-center py-5 opacity-50">SIN ENTREGAS REGISTRADAS</td></tr>';
        });
}

function cargarKardex() {
    const id = document.getElementById('kardexProducto').value;
    const tbody = document.getElementById('kardexList');
    if (!id) { tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 opacity-50">SELECCIONE UN PRODUCTO</td></tr>'; return; }
    const prod = productosInv.find(p => p.id_inventario == id);
    fetch('{{ url("api/almacen/movimientos") }}?id_inventario=' + id, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(res => {
            if (!res.success || !res.data || res.data.length === 0) {
                if (prod && parseFloat(prod.stock_actual) > 0) {
                    tbody.innerHTML = `<tr><td class="fw-bold">${new Date().toISOString().split('T')[0]}</td><td><span class="badge fw-bold px-2 py-1" style="border:2px solid #000;background:#d4edda;color:#000;">COMPRA</span></td><td class="fw-bold" style="color:#007400;">${parseFloat(prod.stock_actual).toFixed(2)}</td><td class="fw-bold" style="color:#dc3545;">—</td><td class="fw-bold">${parseFloat(prod.stock_actual).toFixed(2)}</td><td class="fw-bold">Stock inicial</td></tr>`;
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 opacity-50">SIN MOVIMIENTOS</td></tr>';
                }
                return;
            }
            let totalMov = res.data.reduce((s, m) => s + (m.tipo_movimiento === 'COMPRA' ? parseFloat(m.cantidad || 0) : -parseFloat(m.cantidad || 0)), 0);
            let saldo = prod ? (parseFloat(prod.stock_actual || 0) - totalMov) : 0;
            tbody.innerHTML = res.data.map(m => {
                saldo += m.tipo_movimiento === 'COMPRA' ? parseFloat(m.cantidad || 0) : -parseFloat(m.cantidad || 0);
                return `<tr>
                    <td class="fw-bold">${m.fecha_movimiento}</td>
                    <td><span class="badge fw-bold px-2 py-1" style="border:2px solid #000;background:${m.tipo_movimiento === 'COMPRA' ? '#d4edda' : '#f8d7da'};color:#000;">${m.tipo_movimiento === 'COMPRA' ? 'COMPRA' : 'ENTREGA'}</span></td>
                    <td class="fw-bold" style="color:#007400;">${m.tipo_movimiento === 'COMPRA' ? parseFloat(m.cantidad || 0).toFixed(2) : '—'}</td>
                    <td class="fw-bold" style="color:#dc3545;">${m.tipo_movimiento === 'SALIDA' ? parseFloat(m.cantidad || 0).toFixed(2) : '—'}</td>
                    <td class="fw-bold">${saldo.toFixed(2)}</td>
                    <td class="fw-bold">${m.motivo || m.observaciones || '—'}</td>
                </tr>`;
            }).join('');
        });
}

function loadSaldos() {
    fetch('{{ url("api/almacen") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(res => {
            if (!res.success || !res.data) return;
            const data = res.data;
            document.getElementById('saldoTotalProductos').textContent = data.length;
            const bajo = data.filter(p => p.stock_actual <= p.stock_minimo).length;
            document.getElementById('saldoStockBajo').textContent = bajo;
            const valor = data.reduce((s, p) => s + parseFloat(p.stock_actual || 0) * parseFloat(p.precio_compra || 0), 0);
            document.getElementById('saldoValor').textContent = 'Bs. ' + valor.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            const tbody = document.getElementById('saldosList');
            tbody.innerHTML = data.map(p => {
                const sb = p.stock_actual <= p.stock_minimo;
                return `<tr><td class="fw-bold">${p.nombre_producto}</td>
                    <td class="fw-bold" style="color:${sb ? '#dc3545' : '#007400'};">${parseFloat(p.stock_actual || 0).toFixed(2)}</td>
                    <td class="fw-bold">${parseFloat(p.stock_minimo || 0).toFixed(2)}</td>
                    <td><span class="badge fw-bold px-3 py-2" style="border:2px solid #000;background:${sb ? '#f8d7da' : '#d4edda'};color:${sb ? '#dc3545' : '#007400'};">${sb ? 'BAJO' : 'OK'}</span></td></tr>`;
            }).join('');
        });
}

function mostrarStockProducto() {
    const id = document.getElementById('movIdProducto').value;
    const info = document.getElementById('movStockInfo');
    const tipo = document.getElementById('movTipo').value;
    if (!id) { info.style.display = 'none'; return; }
    const prod = productosInv.find(p => p.id_inventario == id);
    if (!prod) { info.style.display = 'none'; return; }
    const stock = parseFloat(prod.stock_actual || 0);
    const min = parseFloat(prod.stock_minimo || 0);
    const color = stock <= min ? '#dc3545' : '#007400';
    const label = tipo === 'SALIDA' ? 'STOCK DISPONIBLE' : 'STOCK ACTUAL';
    info.innerHTML = `<i class="fas fa-box me-2"></i> ${label}: <span style="color:${color}">${stock.toFixed(2)}</span> ${prod.unidad_medida || ''}`;
    info.style.display = 'block';
}

function autoAsignarConductor() {
    const vId = document.getElementById('movIdVehiculo').value;
    const selP = document.getElementById('movIdPersonal');
    if (!vId) { selP.value = ''; return; }
    const v = vehiculosInv.find(v => v.id_vehiculo == vId);
    if (v && v.id_personal) {
        selP.value = v.id_personal;
    } else {
        selP.value = '';
    }
}

function abrirModalMovimiento(tipo) {
    document.getElementById('movTipo').value = tipo === 'ENTREGA' ? 'SALIDA' : tipo;
    document.getElementById('modalMovTitle').innerHTML = `<i class="fas ${tipo === 'COMPRA' ? 'fa-arrow-down' : 'fa-arrow-up'} me-2"></i> NUEVA ${tipo === 'COMPRA' ? 'COMPRA' : 'ENTREGA'}`;
    document.getElementById('movProveedorRow').style.display = tipo === 'COMPRA' ? 'block' : 'none';
    document.getElementById('movPrecioCompraRow').style.display = tipo === 'COMPRA' ? 'block' : 'none';
    document.getElementById('movVehiculoRow').style.display = tipo === 'ENTREGA' ? 'flex' : 'none';
    document.getElementById('movFecha').value = new Date().toISOString().split('T')[0];
    document.getElementById('movCantidad').value = '';
    document.getElementById('movPrecioUnitario').value = '';
    document.getElementById('movPrecioCompra').value = '';
    document.getElementById('movProveedor').value = '';
    document.getElementById('movObs').value = '';
    document.getElementById('movIdVehiculo').value = '';
    document.getElementById('movIdPersonal').value = '';

    // Generate lot code
    let ultimoNum = 0;
    fetch('{{ url("api/lotes/ultimo") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                const num = parseInt(res.data.codigo_lote.replace('LO-', '')) || 0;
                ultimoNum = num + 1;
            } else {
                ultimoNum = 1;
            }
            document.getElementById('movCodigoLote').value = 'LO-' + String(ultimoNum).padStart(6, '0');
        })
        .catch(() => {
            document.getElementById('movCodigoLote').value = 'LO-000001';
        });

    const selV = document.getElementById('movIdVehiculo');
    selV.innerHTML = '<option value="">SELECCIONE...</option>' + vehiculosInv.map(v =>
        `<option value="${v.id_vehiculo}">${v.placa_vehiculo}</option>`).join('');
    const selP = document.getElementById('movIdPersonal');
    selP.innerHTML = '<option value="">SELECCIONE...</option>' + personalInv.map(p =>
        `<option value="${p.id_personal}">${p.nombres} ${p.apellidos}</option>`).join('');
    document.getElementById('modalMovimiento').style.display = 'flex';
}

function cerrarModalMov() {
    document.getElementById('modalMovimiento').style.display = 'none';
}

function guardarMovimiento(event) {
    event.preventDefault();
    const tipo = document.getElementById('movTipo').value;
    const data = {
        id_inventario: document.getElementById('movIdProducto').value,
        tipo_movimiento: tipo,
        cantidad: document.getElementById('movCantidad').value,
        fecha_movimiento: document.getElementById('movFecha').value,
        precio_unitario: document.getElementById('movPrecioUnitario').value || null,
        codigo_lote: document.getElementById('movCodigoLote').value || null,
        observaciones: document.getElementById('movObs').value,
    };
    if (tipo === 'COMPRA') {
        data.proveedor = document.getElementById('movProveedor').value;
        data.precio_compra = document.getElementById('movPrecioCompra').value || null;
    }
    if (tipo === 'SALIDA') data.id_vehiculo = document.getElementById('movIdVehiculo').value || null;
    if (tipo === 'SALIDA') data.id_personal = document.getElementById('movIdPersonal').value || null;

    if (!data.id_inventario || !data.cantidad) { Swal.fire('Requerido', 'Complete los campos obligatorios', 'warning'); return; }

    if (tipo === 'SALIDA') {
        const prod = productosInv.find(p => p.id_inventario == data.id_inventario);
        if (prod && parseFloat(data.cantidad) > parseFloat(prod.stock_actual || 0)) {
            Swal.fire('Stock Insuficiente', `Solo hay ${parseFloat(prod.stock_actual || 0).toFixed(2)} ${prod.unidad_medida || ''} disponible`, 'error');
            document.getElementById('btnGuardarMov').disabled = false;
            return;
        }
    }

    document.getElementById('btnGuardarMov').disabled = true;
    document.getElementById('btnGuardarMov').innerHTML = '<i class="fas fa-spinner fa-spin"></i> GUARDANDO...';

    fetch('{{ url("api/almacen/movimientos") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('btnGuardarMov').disabled = false;
        document.getElementById('btnGuardarMov').innerHTML = '<i class="fas fa-save"></i> GUARDAR';
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'MOVIMIENTO REGISTRADO', timer: 1500, showConfirmButton: false });
            cerrarModalMov();
            loadProductos(); loadCompras(); loadEntregas(); loadSaldos();
        } else {
            Swal.fire('Error', res.message || 'Error al guardar', 'error');
        }
    });
}

function eliminarProducto(id) {
    Swal.fire({
        title: 'DESACTIVAR PRODUCTO', text: '¿Está seguro?', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'SÍ, DESACTIVAR', cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("almacen") }}/' + id, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => { if (r.redirected) window.location.href = r.url; else loadProductos(); });
        }
    });
}
</script>
@endpush
