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
    color: #fff !important;
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
                    <thead><tr><th>Cód. Fábrica</th><th>Producto</th><th>Cat.</th><th>Unidad</th><th>Stock</th><th>Mín.</th><th>Compra</th><th>Lote</th><th>Acciones</th></tr></thead>
                    <tbody id="productosList"><tr><td colspan="9" class="text-center py-5 opacity-50">CARGANDO...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tabCompras" style="display:none;">
        <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
            <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
                <span><i class="fas fa-arrow-down me-2"></i> Compras (Ingresos a Almacén)</span>
                <a href="{{ route('almacen.movimiento', 'COMPRA') }}" class="btn btn-sm fw-bold text-decoration-none" style="background:#000;color:white;border:3px solid #000;padding:8px 16px;"><i class="fas fa-plus me-1"></i> NUEVA COMPRA</a>
            </div>
                <div class="table-responsive-brutalist">
                    <table class="table-excel mb-0" style="font-size:.85rem;">
                        <thead><tr><th>Fecha</th><th>Código</th><th>Producto</th><th>Cantidad</th><th>P. Unit.</th><th>P. Compra</th><th>Proveedor</th><th>Condición</th><th>Lote</th><th>Acciones</th></tr></thead>
                        <tbody id="comprasList"><tr><td colspan="9" class="text-center py-5 opacity-50">CARGANDO...</td></tr></tbody>
                    </table>
                </div>
        </div>
    </div>

    <div id="tabEntregas" style="display:none;">
        <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
            <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
                <span><i class="fas fa-arrow-up me-2"></i> Entregas (Salidas de Almacén)</span>
                <a href="{{ route('almacen.movimiento', 'ENTREGA') }}" class="btn btn-sm fw-bold text-decoration-none" style="background:#000;color:#fff;border:3px solid #000;padding:8px 16px;"><i class="fas fa-plus me-1"></i> NUEVA ENTREGA</a>
            </div>
                <div class="table-responsive-brutalist">
                    <table class="table-excel mb-0" style="font-size:.85rem;">
                        <thead><tr><th>Fecha</th><th>Código</th><th>Producto</th><th>Cantidad</th><th>P. Unit.</th><th>Vehículo</th><th>Conductor</th><th>Acciones</th></tr></thead>
                        <tbody id="entregasList"><tr><td colspan="7" class="text-center py-5 opacity-50">CARGANDO...</td></tr></tbody>
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
                <div class="p-3 text-center" style="background:#f8d7da;border:3px solid #000;">
                    <div class="small fw-bold">Stock Cero</div>
                    <div class="fs-5 fw-bold" style="color:#dc3545;" id="saldoStockCero">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 text-center" style="background:#cce5ff;border:3px solid #000;">
                    <div class="small fw-bold">Valor Inventario</div>
                    <div class="fs-5 fw-bold" id="saldoValor">Bs. 0</div>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="p-3 text-center" style="background:#e8d5f5;border:3px solid #000;">
                    <div class="small fw-bold">Total Inversión</div>
                    <div class="fs-5 fw-bold" id="saldoTotalInversion">Bs. 0</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 text-center" style="background:#d1ecf1;border:3px solid #000;">
                    <div class="small fw-bold">Valor Promedio x Producto</div>
                    <div class="fs-5 fw-bold" id="saldoPromedio">Bs. 0</div>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <input type="text" class="form-control fw-bold" id="filtroSaldos" style="border-radius:0;border:3px solid #000;padding:12px;" placeholder="FILTRAR POR PRODUCTO O CÓDIGO..." oninput="filtrarSaldos()">
        </div>
        <div class="bento-card p-0 border-black" style="border-width:4px;overflow:hidden;">
            <div class="table-responsive-brutalist">
                <table class="table-excel mb-0" style="font-size:.82rem;">
                    <thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Unidad</th><th>Stock</th><th>Mín.</th><th>P. Compra</th><th>Valor Total</th><th>Estado</th></tr></thead>
                    <tbody id="saldosList"><tr><td colspan="9" class="text-center py-5 opacity-50">CARGANDO...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let productosInv = [];

document.addEventListener('DOMContentLoaded', function() {
    loadProductos();
    loadCompras();
    loadEntregas();
    loadSaldos();

    // Ya no se precargan unidades ni personal: el formulario de movimientos
    // es otra pagina y carga sus propias listas al abrirse.
    fetch('{{ url("api/almacen") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(res => {
            if (res.success) {
                productosInv = res.data || [];

                // El selector de productos del formulario de movimientos se
                // llena ahora en `form-alm.blade.php`; aquí solo queda el del
                // kardex, que sigue dentro de esta página.
                const selK = document.getElementById('kardexProducto');
                selK.innerHTML = '<option value="">SELECCIONE PRODUCTO...</option>' + (res.data || []).map(p =>
                    `<option value="${p.id_inventario}">${esc(p.codigo_barras || p.codigo)} - ${esc(p.nombre_producto)}</option>`).join('');
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
                const sb = parseFloat(p.stock_actual || 0) <= parseFloat(p.stock_minimo || 0);
                return `<tr>
                    <td class="font-bold"><span class="badge bg-black text-white px-2">${esc(p.codigo_barras || p.codigo)}</span></td>
                    <td class="font-bold">${esc(p.nombre_producto)}</td>
                    <td><span class="badge font-bold px-2 py-1" style="background:#2f2c79;color:#fff;border:2px solid #000;">${esc(p.categoria)}</span></td>
                    <td class="font-bold">${esc(p.unidad_medida)}</td>
                    <td class="font-bold" style="color:${sb ? '#dc3545' : '#007400'};">${bs(p.stock_actual || 0)}</td>
                    <td class="font-bold">${bs(p.stock_minimo || 0)}</td>
                    <td class="font-bold">Bs. ${bs(p.precio_compra || 0)}</td>
                    <td class="font-bold"><span class="badge bg-black text-white px-2" style="font-family:monospace;">${esc(p.codigo_lote)}</span></td>
                    <td>
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm btn-danger border-black font-bold" onclick="eliminarProducto(${Number(p.id_inventario)})"><i class="fas fa-ban"></i></button>
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
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 opacity-50">SIN MOVIMIENTOS</td></tr>'; return;
            }
tbody.innerHTML = res.data.filter(m => m.tipo_movimiento === 'COMPRA').map(m => {
                  const total = parseFloat(m.cantidad || 0) * parseFloat(m.costo_unitario || 0);
                  const cond = m.condicion_pago || 'CONTADO';
                  const condColor = cond === 'CREDITO' ? '#ffdcd6' : '#d4edda';
                  return `<tr><td class="fw-bold">${esc(m.fecha_movimiento)}</td><td class="fw-bold"><span class="badge bg-black text-white px-2">${esc(m.codigo_barras || m.codigo)}</span></td><td class="fw-bold">${txt(m.nombre_producto)}</td><td class="fw-bold">${bs(m.cantidad || 0)}</td><td class="fw-bold">Bs. ${bs(m.costo_unitario || 0)}</td><td class="fw-bold">Bs. ${bs(total)}</td><td class="fw-bold">${txt(m.proveedor)}</td><td><span class="badge fw-bold px-2 py-1" style="background:${condColor};color:#000;border:2px solid #000;">${esc(cond)}</span></td><td class="fw-bold"><span class="badge bg-black text-white px-2" style="font-family:monospace;">${esc(m.codigo_lote)}</span></td><td><a href="{{ url('almacen/movimiento/COMPRA') }}/${Number(m.id_movimiento)}" class="btn btn-sm btn-outline-dark btn-editar-mov" style="border:2px solid #000;padding:4px 8px;" title="EDITAR"><i class="fas fa-edit"></i></a></td></tr>`;
              }).join('') || '<tr><td colspan="10" class="text-center py-5 opacity-50">SIN COMPRAS REGISTRADAS</td></tr>';
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
                  `<tr><td class="fw-bold">${esc(m.fecha_movimiento)}</td><td class="fw-bold"><span class="badge bg-black text-white px-2">${esc(m.codigo_barras || m.codigo)}</span></td><td class="fw-bold">${txt(m.nombre_producto)}</td><td class="fw-bold">${bs(m.cantidad || 0)}</td><td class="fw-bold">Bs. ${bs(m.costo_unitario || 0)}</td><td class="fw-bold">${txt(m.placa_vehiculo)}</td><td class="fw-bold">${txt(m.conductor)}</td><td><a href="{{ url('almacen/movimiento/ENTREGA') }}/${Number(m.id_movimiento)}" class="btn btn-sm btn-outline-dark btn-editar-mov" style="border:2px solid #000;padding:4px 8px;" title="EDITAR"><i class="fas fa-edit"></i></a></td></tr>`
              ).join('') || '<tr><td colspan="8" class="text-center py-5 opacity-50">SIN ENTREGAS REGISTRADAS</td></tr>';
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
                    <td class="fw-bold">${esc(m.fecha_movimiento)}</td>
                    <td><span class="badge fw-bold px-2 py-1" style="border:2px solid #000;background:${m.tipo_movimiento === 'COMPRA' ? '#d4edda' : '#f8d7da'};color:#000;">${m.tipo_movimiento === 'COMPRA' ? 'COMPRA' : 'ENTREGA'}</span></td>
                    <td class="fw-bold" style="color:#007400;">${m.tipo_movimiento === 'COMPRA' ? bs(m.cantidad || 0) : '—'}</td>
                    <td class="fw-bold" style="color:#dc3545;">${m.tipo_movimiento === 'SALIDA' ? bs(m.cantidad || 0) : '—'}</td>
                    <td class="fw-bold">${bs(saldo)}</td>
                    <td class="fw-bold">${txt(m.motivo || m.observaciones)}</td>
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
            const bajo = data.filter(p => parseFloat(p.stock_actual || 0) <= parseFloat(p.stock_minimo || 0)).length;
            document.getElementById('saldoStockBajo').textContent = bajo;
            const cero = data.filter(p => parseFloat(p.stock_actual || 0) === 0).length;
            document.getElementById('saldoStockCero').textContent = cero;
            const valor = data.reduce((s, p) => s + parseFloat(p.stock_actual || 0) * parseFloat(p.ultimo_precio || p.precio_compra || 0), 0);
            document.getElementById('saldoValor').textContent = 'Bs. ' + valor.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            document.getElementById('saldoTotalInversion').textContent = 'Bs. ' + valor.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            const prom = data.length > 0 ? (valor / data.length) : 0;
            document.getElementById('saldoPromedio').textContent = 'Bs. ' + prom.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            window.saldosData = data;
            renderSaldos(data);
        });
}

function renderSaldos(data) {
    const tbody = document.getElementById('saldosList');
    tbody.innerHTML = data.map(p => {
        const sb = parseFloat(p.stock_actual || 0) <= parseFloat(p.stock_minimo || 0);
        const precio = parseFloat(p.ultimo_precio || p.precio_compra || 0);
        const total = parseFloat(p.stock_actual || 0) * precio;
        return `<tr>
            <td class="fw-bold"><span class="badge bg-black text-white px-2" style="font-family:monospace;">${esc(p.codigo_barras || p.codigo)}</span></td>
            <td class="fw-bold">${esc(p.nombre_producto)}</td>
            <td class="fw-bold">${txt(p.categoria)}</td>
            <td class="fw-bold">${txt(p.unidad_medida)}</td>
            <td class="fw-bold" style="color:${sb ? '#dc3545' : '#007400'};">${bs(p.stock_actual || 0)}</td>
            <td class="fw-bold">${bs(p.stock_minimo || 0)}</td>
            <td class="fw-bold">Bs. ${bs(precio)}</td>
            <td class="fw-bold">Bs. ${bs(total)}</td>
            <td><span class="badge fw-bold px-3 py-2" style="border:2px solid #000;background:${sb ? '#f8d7da' : '#d4edda'};color:${sb ? '#dc3545' : '#007400'};">${sb ? 'BAJO' : 'OK'}</span></td>
        </tr>`;
    }).join('');
}

function filtrarSaldos() {
    const filtro = document.getElementById('filtroSaldos').value.toLowerCase();
    const data = (window.saldosData || []).filter(p =>
        (p.nombre_producto || '').toLowerCase().includes(filtro) ||
        (p.codigo || '').toLowerCase().includes(filtro) ||
        (p.categoria || '').toLowerCase().includes(filtro)
    );
    renderSaldos(data);
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
