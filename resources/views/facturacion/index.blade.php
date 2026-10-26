@extends('layouts.master')

@section('title', 'Facturación')

@push('styles')
<style>
.factura-card {
    border:4px solid var(--line);
    margin-bottom: 1rem;
    background: #fff;
}
.factura-header {
    border-bottom:2px solid var(--line-strong);
    cursor: pointer;
    transition: background 0.2s;
}
.factura-header:hover {
    background: var(--sun-soft);
}
.tab-btn {
    border: none;
    border-radius: 0;
    font-weight: 800;
    padding: 14px 16px;
    font-size: 0.95rem;
    flex: 1;
    transition: all 0.2s;
}
.tab-btn.active {
    background: var(--primary) !important;
    color: var(--primary) !important;
}
.tab-btn:not(.active) {
    background: #fff;
    color: var(--ink);
    border-right:4px solid var(--line);
}
.tab-btn:not(.active):last-child {
    border-right: none;
}
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
</style>
@endpush

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">FACTURACIÓN</h1>
            <p class="font-bold small text-black uppercase">Control de Ingresos y Facturación de Fletes</p>
        </div>
    </header>

    <!-- TABS -->
    <div class="d-flex mb-4" style="border:4px solid var(--line);">
        <button class="tab-btn active" id="tabFletes" onclick="switchTab('fletes')">
            <i class="fas fa-truck me-2"></i> FLETES
        </button>
        <button class="tab-btn" id="tabListado" onclick="switchTab('listado')">
            <i class="fas fa-list me-2"></i> FACTURAS
        </button>
        <button class="tab-btn" id="tabFacturacion" onclick="switchTab('facturacion')">
            <i class="fas fa-file-invoice me-2"></i> FACTURACIÓN
        </button>
    </div>

    <!-- ================================================================ -->
    <!-- TAB 1: FLETES (todos los ingresos) -->
    <!-- ================================================================ -->
    <div id="tabContentFletes">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-bold fs-5">TODOS LOS FLETES</div>
            
        </div>

        <!-- Filtros fletes -->
        <div class="p-3 mb-3" style="background:var(--primary);color:#fff;border:4px solid var(--line);">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">FECHA INICIO</label>
                    <input type="date" class="form-control fw-bold" id="filtroFletesInicio" style="border-radius:10px;border:3px solid var(--line-strong);padding:8px;">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">FECHA FIN</label>
                    <input type="date" class="form-control fw-bold" id="filtroFletesFin" style="border-radius:10px;border:3px solid var(--line-strong);padding:8px;">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">ESTADO</label>
                    <select class="form-control fw-bold" id="filtroFletesEstado" style="border-radius:10px;border:3px solid var(--line-strong);padding:8px;">
                        <option value="">TODOS</option>
                        <option value="PENDIENTE">PENDIENTE</option>
                        <option value="FACTURADA">FACTURADA</option>
                        <option value="COBRADO">COBRADO</option>
                        <option value="ANULADA">ANULADA</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn fw-bold w-100" style="border:3px solid var(--line-strong);background: var(--primary);color:#fff;padding:8px;" onclick="cargarFletes()">
                        <i class="fas fa-search"></i> FILTRAR
                    </button>
                </div>
            </div>
        </div>

        <div id="fletesLoading" class="text-center py-5" style="display:none;">
            <div class="spinner-border text-dark" role="status"></div>
        </div>
        <div id="fletesEmpty" class="text-center py-5" style="display:none;border:4px solid var(--line);">
            <i class="fas fa-truck" style="font-size:64px;opacity:.2;"></i>
            <h3 class="mt-3 fw-bold">NO HAY FLETES REGISTRADOS</h3>
        </div>
        <div id="fletesContainer" style="display:none;">
            <div style="border:4px solid var(--line);overflow:hidden;">
                <table class="table-excel mb-0" style="font-size:.9rem;">
                    <thead>
<tr>
    <th>FECHA</th>
    <th>N° DOC</th>
    <th>UNIDAD</th>
    <th>CLIENTE</th>
    <th>RUTA</th>
    <th>CONCEPTO</th>
    <th>MONTO</th>
    <th>TONELADAS</th>
    <th>TIPO CAMBIO</th>
    <th>ESTADO</th>
    <th>ACCIONES</th>
</tr>
                    </thead>
                    <tbody id="fletesBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB 2: LISTADO DE FACTURAS (agrupado) -->
    <!-- ================================================================ -->
    <div id="tabContentListado" style="display:none;">
        <div id="listadoLoading" class="text-center py-5">
            <div class="spinner-border text-dark" role="status"></div>
        </div>
        <div id="listadoEmpty" class="text-center py-5" style="display:none;">
            <i class="fas fa-file-invoice" style="font-size:64px;opacity:.2;"></i>
            <h3 class="mt-3 fw-bold">NO HAY FACTURAS REGISTRADAS</h3>
        </div>
        <div id="listadoContainer"></div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB 3: FACTURACIÓN (batch) -->
    <!-- ================================================================ -->
    <div id="tabContentFacturacion" style="display:none;">
        <div class="p-3 mb-4" style="background:var(--primary);color:#fff;border:4px solid var(--line);">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">FECHA INICIO</label>
                    <input type="date" class="form-control fw-bold" id="filterFechaInicio" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">FECHA FIN</label>
                    <input type="date" class="form-control fw-bold" id="filterFechaFin" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">CLIENTE</label>
                    <input type="text" class="form-control fw-bold" id="filterCliente" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;" placeholder="Buscar cliente...">
                </div>
                <div class="col-md-3">
                    <button class="btn fw-bold w-100" style="border:3px solid var(--line-strong);background: var(--primary);color:#fff;padding:10px;" onclick="cargarPendientes()">
                        <i class="fas fa-search"></i> FILTRAR
                    </button>
                </div>
            </div>
        </div>
        <div id="selectionBar" class="d-flex justify-content-between align-items-center mb-3 p-3" style="background:#fff;border:4px solid var(--line);display:none;">
            <div class="d-flex align-items-center gap-3">
                <span class="fw-bold fs-5" id="selectedCount">0 seleccionado(s)</span>
                <span class="fw-bold fs-5" style="color:var(--success);" id="selectedTotal">Total: Bs. 0.00</span>
            </div>
            <button class="btn fw-bold d-flex align-items-center gap-2" style="background: var(--primary);color:#fff;border:4px solid var(--line);padding:12px 24px;font-size:1rem;" onclick="abrirModalFacturar()">
                <i class="fas fa-file-invoice"></i> FACTURAR SELECCIONADOS
            </button>
        </div>
        <div id="pendientesLoading" class="text-center py-5" style="display:none;">
            <div class="spinner-border text-dark" role="status"></div>
        </div>
        <div id="pendientesContainer"></div>
    </div>
</div>

<!-- ================================================================ -->
<!-- MODAL NUEVO FLETE -->
<!-- ================================================================ -->
<div class="modal-overlay-fact" id="modalNuevoFlete" style="display:none;" onclick="if(event.target===this)cerrarModalNuevoFlete()">
    <div class="modal-content-fact" onclick="event.stopPropagation()">
        <div class="p-3" style="background: var(--primary);color:#fff;display:flex;justify-content:space-between;align-items:center;">
            <h3 class="mb-0 fw-bold fs-5"><i class="fas fa-plus-circle me-2"></i> NUEVO FLETE (INGRESO)</h3>
            <button class="btn btn-sm text-white fw-bold" onclick="cerrarModalNuevoFlete()" style="font-size:1.5rem;line-height:1;">×</button>
        </div>
        <div class="p-3" style="background:#fff;border:4px solid var(--line);border-top:none;">
            <form id="formNuevoFlete" onsubmit="return guardarFlete(event)">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="fw-bold small text-uppercase">FECHA <span class="text-danger">*</span></label>
                        <input type="date" class="form-control fw-bold" id="nf_fecha_ingreso" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small text-uppercase">UNIDAD <span class="text-danger">*</span></label>
                        <select class="form-control fw-bold" id="nf_id_vehiculo" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;" required>
                            <option value="">SELECCIONE...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small text-uppercase">CONDUCTOR</label>
                        <select class="form-control fw-bold" id="nf_id_personal" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;">
                            <option value="">SELECCIONE...</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="fw-bold small text-uppercase">RUTA</label>
                        <select class="form-control fw-bold" id="nf_id_tramo" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;" onchange="seleccionarRutaNF(this)">
                            <option value="">SELECCIONE RUTA...</option>
                        </select>
                        <button type="button" id="btnAplicarRutaNF" class="btn btn-sm fw-bold mt-2 w-100" style="border-radius:999px;border:3px solid var(--line-strong);padding:8px;background:var(--primary);color:#fff;">
                            <i class="fas fa-rotate-right me-1"></i> APLICAR RUTA
                        </button>
                        <small class="d-block mt-2 fw-bold" style="color:#666;">Al elegir la ruta se completan origen, destino, km y Bs/TON.</small>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">ORIGEN</label>
                        <input type="text" class="form-control fw-bold" id="nf_origen" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;background:var(--primary-soft)!important;cursor:not-allowed;" placeholder="Ciudad origen" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">DESTINO</label>
                        <input type="text" class="form-control fw-bold" id="nf_destino" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;background:var(--primary-soft)!important;cursor:not-allowed;" placeholder="Ciudad destino" readonly>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">KM</label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="nf_kilometraje" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;" min="0" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">BS X TON</label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="nf_precio_ton" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;" min="0" placeholder="Bs por tonelada">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">TONELADAS</label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="nf_toneladas" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;" min="0" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">MONTO (Bs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="nf_monto" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;background:var(--primary-soft)!important;cursor:not-allowed;" required min="0" placeholder="0.00" readonly>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">TIPO DE CAMBIO (Bs/USD)</label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="nf_tipo_cambio" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;background:var(--primary-soft)!important;cursor:not-allowed;" min="0" placeholder="6.96" readonly value="{{ $tipoCambioVigente ?? old('tipo_cambio', '') }}">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="fw-bold small text-uppercase">CLIENTE / PROVEEDOR</label>
                        <input type="text" class="form-control fw-bold" id="nf_cliente_nombre" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;" value="INDUSTRIAS OLEAGINOSAS S.A." placeholder="INDUSTRIAS OLEAGINOSAS S.A.">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="fw-bold small text-uppercase">CONCEPTO <span class="text-danger">*</span></label>
                        <input type="text" class="form-control fw-bold" id="nf_concepto" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;" required value="TRANSPORTE DE SOYA" placeholder="TRANSPORTE DE SOYA">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">TIPO PAGO</label>
                        <select class="form-control fw-bold" id="nf_tipo_pago" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;">
                            <option value="EFECTIVO">EFECTIVO</option>
                            <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                            <option value="CHEQUE">CHEQUE</option>
                            <option value="OTRO">OTRO</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">FECHA VENCIMIENTO</label>
                        <input type="date" class="form-control fw-bold" id="nf_fecha_vencimiento" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;">
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="fw-bold small text-uppercase">OBSERVACIONES</label>
                    <textarea class="form-control fw-bold" id="nf_observaciones" rows="2" style="border-radius:10px;border:3px solid var(--line-strong);padding:10px;" placeholder="Observaciones..."></textarea>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2" style="background: var(--primary);color:#fff;border:4px solid var(--line);padding:12px;font-size:1rem;" id="btnGuardarFlete">
                        <i class="fas fa-save"></i> GUARDAR FLETE
                    </button>
                    <button type="button" class="btn fw-bold" style="border:4px solid var(--line);padding:12px;font-size:1rem;" onclick="cerrarModalNuevoFlete()">CANCELAR</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- MODAL FACTURAR (batch) -->
<!-- ================================================================ -->
<div class="modal-overlay-fact" id="modalFacturar" style="display:none;" onclick="if(event.target===this)cerrarModalFacturar()">
    <div class="modal-content-fact" onclick="event.stopPropagation()">
        <div class="p-4" style="background: var(--primary);color:#fff;">
            <h3 class="mb-0 fw-bold fs-4"><i class="fas fa-file-invoice me-2"></i> FACTURAR <span id="modalCount">0</span> FLETE(S)</h3>
        </div>
        <div class="p-4" style="background:#fff;border:4px solid var(--line);border-top:none;">
            <div class="row g-3">
                <div class="col-12">
                    <label class="fw-bold text-uppercase small">FECHA FACTURA</label>
                    <input type="date" class="form-control fw-bold" id="modalFechaFactura" style="border-radius:10px;border:3px solid var(--line-strong);padding:12px;font-size:1rem;">
                </div>
                <div class="col-12">
                    <label class="fw-bold text-uppercase small">NÚMERO DE FACTURA <span class="text-danger">*</span></label>
                    <input type="text" class="form-control fw-bold" id="modalNumeroFactura" style="border-radius:10px;border:3px solid var(--line-strong);padding:12px;font-size:1rem;" placeholder="Ej: 123">
                </div>
                <div class="col-12">
                    <label class="fw-bold text-uppercase small">RAZÓN SOCIAL <span class="text-danger">*</span></label>
                    <input type="text" class="form-control fw-bold" id="modalClienteNombre" style="border-radius:10px;border:3px solid var(--line-strong);padding:12px;font-size:1rem;" placeholder="Nombre del cliente">
                </div>
                <div class="col-12">
                    <div class="p-3 fw-bold fs-5 text-center" style="background:var(--primary);color:#fff;border:3px solid var(--line-strong);" id="modalTotal">TOTAL: Bs. 0.00</div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="btn fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2" style="background: var(--primary);color:#fff;border:4px solid var(--line);padding:14px;font-size:1.1rem;" onclick="confirmarBatchFacturar()" id="btnConfirmarFactura">
                    <i class="fas fa-check"></i> CONFIRMAR FACTURA
                </button>
                <button class="btn fw-bold" style="border:4px solid var(--line);padding:14px;font-size:1.1rem;" onclick="cerrarModalFacturar()">CANCELAR</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let activeTab = 'fletes';
let seleccionadas = new Set();
let pendientesData = [];
let vehiculosList = [];
let personalList = [];
let tramosList = [];
let configData = { tipo_cambio: 6.96, precio_tonelada_usd: 13 };

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('nf_fecha_ingreso').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalFechaFactura').value = new Date().toISOString().split('T')[0];
    fetch('{{ url("api/config") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(res => { if (res.success) configData = res.data; });
    cargarCombos();
    cargarFletes();
    cargarListado();
    cargarPendientes();
});

function cargarCombos() {
    fetch('{{ url("api/vehiculos") }}?estado=1', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        vehiculosList = res.data || [];
        const sel = document.getElementById('nf_id_vehiculo');
        sel.innerHTML = '<option value="">SELECCIONE...</option>' +
            vehiculosList.map(v => `<option value="${v.id_vehiculo}">${v.placa_vehiculo} - ${v.marca || ''}</option>`).join('');
    });

    fetch('{{ url("api/personal") }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        personalList = res.data || [];
        const selP = document.getElementById('nf_id_personal');
        selP.innerHTML = '<option value="">SELECCIONE...</option>' +
            personalList.filter(p => p.estado == 1).map(p =>
                `<option value="${p.id_personal}">${p.nombres || ''} ${p.apellidos || ''}</option>`).join('');
    });

    fetch('{{ url("api/tramos") }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        tramosList = res.data || [];
        const selT = document.getElementById('nf_id_tramo');
        if (selT) {
            // `data-origen` y `data-destino` son lo que rellena los campos de
            // abajo. Sin ellos el modal mostraba la ruta en el select pero
            // dejaba ORIGEN y DESTINO en su placeholder.
            selT.innerHTML = '<option value="">SELECCIONE RUTA...</option>' +
                tramosList.map(t => `<option value="${t.id_tramo}" data-origen="${window.esc(t.origen || '')}" data-destino="${window.esc(t.destino || '')}" data-precio-ton="${t.precio_dolar_tonelada || 0}" data-km="${t.kilometros || 0}">${window.esc(t.origen || '')} → ${window.esc(t.destino || '')} (Bs. ${t.precio_total})</option>`).join('');
        }
    });
}

function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tabContentFletes').style.display = tab === 'fletes' ? 'block' : 'none';
    document.getElementById('tabContentListado').style.display = tab === 'listado' ? 'block' : 'none';
    document.getElementById('tabContentFacturacion').style.display = tab === 'facturacion' ? 'block' : 'none';
    const map = { fletes: 'Fletes', listado: 'Listado', facturacion: 'Facturacion' };
    document.getElementById('tab' + map[tab]).classList.add('active');
    if (tab === 'facturacion') {
        actualizarSelectionBar();
        cargarPendientes();
    }
}

// ================================================================
// TAB 1: FLETES (todos los ingresos)
// ================================================================
function cargarFletes() {
    const params = new URLSearchParams();
    const fi = document.getElementById('filtroFletesInicio').value;
    const ff = document.getElementById('filtroFletesFin').value;
    const est = document.getElementById('filtroFletesEstado').value;
    if (fi) params.append('fecha_inicio', fi);
    if (ff) params.append('fecha_fin', ff);
    if (est) params.append('estado', est);

    document.getElementById('fletesLoading').style.display = 'block';
    document.getElementById('fletesContainer').style.display = 'none';
    document.getElementById('fletesEmpty').style.display = 'none';

    fetch('{{ url("api/facturacion") }}?' + params.toString(), {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('fletesLoading').style.display = 'none';
        if (!res.success || !res.data) {
            document.getElementById('fletesEmpty').innerHTML = '<i class="fas fa-truck" style="font-size:64px;opacity:.2;"></i><h3 class="mt-3 fw-bold text-danger">ERROR AL CARGAR FLETES</h3>';
            document.getElementById('fletesEmpty').style.display = 'block';
            return;
        }
        if (res.data.length === 0) {
            document.getElementById('fletesEmpty').style.display = 'block';
            return;
        }
        document.getElementById('fletesContainer').style.display = 'block';
        const tbody = document.getElementById('fletesBody');
        tbody.innerHTML = res.data.map(f => {
            const estadoColor = f.estado_factura === 'COBRADO' ? 'var(--success)' :
                f.estado_factura === 'FACTURADA' ? 'var(--ink)' :
                f.estado_factura === 'ANULADA' ? 'var(--soft-danger)' : 'var(--ink)';
            const estadoBg = f.estado_factura === 'COBRADO' ? 'var(--mint)' :
                f.estado_factura === 'FACTURADA' ? 'var(--sun-soft)' :
                f.estado_factura === 'ANULADA' ? 'var(--coral-soft)' : 'var(--primary-soft)';
            const ruta = (f.origen || '') + (f.destino ? ' - ' + f.destino : '');
            return `<tr>
                <td class="fw-bold" style="white-space:nowrap;">${f.fecha_ingreso || '—'}</td>
                <td class="fw-bold font-monospace">${f.nro_documento || '—'}</td>
                <td class="fw-bold">${f.placa_vehiculo || '—'}</td>
                <td>${f.cliente_nombre || '—'}</td>
                <td>${ruta || '—'}</td>
                <td>${f.concepto || '—'}</td>
                <td class="fw-bold" style="color:var(--success);">${formatCurrency(f.monto)}</td>
                <td>${f.toneladas || '—'}</td>
                <td class="fw-bold font-monospace">${f.tipo_cambio ? 'Bs ' + parseFloat(f.tipo_cambio).toFixed(2) : '—'}</td>
                <td><span class="badge fw-bold px-2 py-1" style="border:2px solid var(--line-strong);background:${estadoBg};color:${estadoColor};">${f.estado_factura || '—'}</span></td>
                <td>
                    <div class="d-flex gap-1 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black fw-bold" onclick="editarFlete(${f.id_ingreso})" title="EDITAR"><i class="fas fa-edit"></i></button>
                        ${f.estado_factura !== 'ANULADA' ? `<button class="btn btn-sm btn-danger border-black fw-bold" onclick="anularFlete(${f.id_ingreso})" title="ANULAR"><i class="fas fa-ban"></i></button>` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('');
    })
    .catch(() => {
        document.getElementById('fletesLoading').style.display = 'none';
        document.getElementById('fletesEmpty').innerHTML = '<i class="fas fa-truck" style="font-size:64px;opacity:.2;"></i><h3 class="mt-3 fw-bold text-danger">ERROR DE CONEXIÓN</h3>';
        document.getElementById('fletesEmpty').style.display = 'block';
    });
}

function editarFlete(id) {
    window.location.href = '{{ url("facturacion") }}/' + id + '/editar';
}

function anularFlete(id) {
    Swal.fire({
        title: 'ANULAR FLETE',
        text: '¿Está seguro de anular este flete?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, ANULAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#d6336c',
    }).then(result => {
        if (result.isConfirmed) {
            fetch('{{ url("facturacion") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ '_method': 'DELETE' })
            }).then(r => {
                if (r.redirected) window.location.href = r.url;
                else { cargarFletes(); cargarListado(); cargarPendientes(); }
            });
        }
    });
}

/**
 * Aplica la ruta elegida a los campos que dependen de ella.
 *
 * Se lee del `data-` de la opcion y no de `tramosList`: el array se arma con
 * un fetch aparte y si todavia no volvio, la busqueda fallaba en silencio y
 * no rellenaba nada.
 */
function seleccionarRutaNF(select) {
    const opt = select.options[select.selectedIndex];
    const origen = document.getElementById('nf_origen');
    const destino = document.getElementById('nf_destino');
    const kilometros = document.getElementById('nf_kilometraje');
    const precioTon = document.getElementById('nf_precio_ton');

    // Volver a "SELECCIONE RUTA..." limpia: si no, el select muestra una
    // ruta y los campos de abajo los datos de otra.
    if (!opt || !opt.value) {
        origen.value = '';
        destino.value = '';
        kilometros.value = '0';
        precioTon.value = '';
        recalcularMontoNF();
        return;
    }

    origen.value = opt.dataset.origen || '';
    destino.value = opt.dataset.destino || '';
    kilometros.value = opt.dataset.km || '0';

    const precio = parseFloat(opt.dataset.precioTon) || 0;
    precioTon.value = precio ? precio.toFixed(2) : '';

    // Las toneladas no se tocan: son del flete, no de la ruta. Se recalcula
    // el monto para que se vea el efecto del nuevo Bs/TON sobre lo escrito.
    recalcularMontoNF();
}

/**
 * MONTO = TONELADAS x BS/TON.
 *
 * Antes solo recalculaba con toneladas > 0, dejando el monto de la carga
 * anterior cuando el campo quedaba vacio: por eso el total no se actualizaba
 * solo y había que cambiar de ruta para "despertarlo".
 */
function recalcularMontoNF() {
    const ton = parseFloat(document.getElementById('nf_toneladas').value) || 0;
    const precioTon = parseFloat(document.getElementById('nf_precio_ton').value) || 0;
    const monto = document.getElementById('nf_monto');

    if (ton > 0 && precioTon > 0) {
        monto.value = (ton * precioTon).toFixed(2);
        return;
    }

    // Sin datos suficientes se pone 0 en vez de conservar un total viejo.
    monto.value = '0';
}

document.addEventListener('input', function(e) {
    if (e.target.id === 'nf_toneladas' || e.target.id === 'nf_precio_ton') {
        recalcularMontoNF();
    }
});

// Volver a marcar la MISMA ruta no dispara `onchange`, asi que sin esto el
// operador se equivoca de ruta y no tiene forma de recargarla.
document.getElementById('btnAplicarRutaNF')?.addEventListener('click', function () {
    seleccionarRutaNF(document.getElementById('nf_id_tramo'));
});

// --- Modal Nuevo Flete ---
function abrirModalNuevoFlete() {
    document.getElementById('formNuevoFlete').reset();
    document.getElementById('nf_fecha_ingreso').value = new Date().toISOString().split('T')[0];
    document.getElementById('nf_cliente_nombre').value = 'INDUSTRIAS OLEAGINOSAS S.A.';
    document.getElementById('nf_concepto').value = 'TRANSPORTE DE SOYA';
    document.getElementById('nf_monto').value = '';
    document.getElementById('nf_toneladas').value = '0';
    document.getElementById('nf_kilometraje').value = '0';
    document.getElementById('nf_precio_ton').value = '';
    const selR = document.getElementById('nf_id_tramo');
    if (selR) selR.value = '';
    // `reset()` no siempre limpia los campos readonly que rellena el script.
    ['nf_origen', 'nf_destino', 'nf_precio_ton'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    recalcularMontoNF();
    document.getElementById('modalNuevoFlete').style.display = 'flex';
}

function cerrarModalNuevoFlete() {
    document.getElementById('modalNuevoFlete').style.display = 'none';
}

function guardarFlete(event) {
    event.preventDefault();
    const data = {
        id_vehiculo: document.getElementById('nf_id_vehiculo').value,
        id_personal: document.getElementById('nf_id_personal').value || null,
        // Sin esto la ruta no se guardaba: el modal armaba el envio a mano y
        // se le habia olvidado el campo, aunque el select viniera elegido.
        id_tramo: document.getElementById('nf_id_tramo').value || null,
        cliente_nombre: document.getElementById('nf_cliente_nombre').value,
        monto: document.getElementById('nf_monto').value,
        tipo_cambio: document.getElementById('nf_tipo_cambio').value || null,
        origen: document.getElementById('nf_origen').value,
        destino: document.getElementById('nf_destino').value,
        toneladas: document.getElementById('nf_toneladas').value || 0,
        kilometraje_conducido: document.getElementById('nf_kilometraje').value || 0,
        concepto: document.getElementById('nf_concepto').value,
        fecha_ingreso: document.getElementById('nf_fecha_ingreso').value,
        fecha_vencimiento: document.getElementById('nf_fecha_vencimiento').value || null,
        tipo_pago: document.getElementById('nf_tipo_pago').value,
        observaciones: document.getElementById('nf_observaciones').value,
    };

    if (!data.id_vehiculo) { Swal.fire('Requerido', 'Seleccione una unidad', 'warning'); return; }
    if (!data.concepto) { Swal.fire('Requerido', 'El concepto es obligatorio', 'warning'); return; }
    if (!data.monto || parseFloat(data.monto) <= 0) { Swal.fire('Requerido', 'El monto debe ser mayor a 0', 'warning'); return; }

    document.getElementById('btnGuardarFlete').disabled = true;
    document.getElementById('btnGuardarFlete').innerHTML = '<i class="fas fa-spinner fa-spin"></i> GUARDANDO...';

    fetch('{{ route("facturacion.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(async r => {
        const text = await r.text();
        try { return JSON.parse(text); }
        catch (e) { return { success: false, message: 'Error del servidor: HTTP ' + r.status + ' - ' + text.substring(0, 200) }; }
    })
    .then(res => {
        document.getElementById('btnGuardarFlete').disabled = false;
        document.getElementById('btnGuardarFlete').innerHTML = '<i class="fas fa-save"></i> GUARDAR FLETE';
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'FLETE REGISTRADO', text: res.message || 'Ingreso registrado exitosamente', timer: 2000, showConfirmButton: false });
            cerrarModalNuevoFlete();
            cargarFletes();
            cargarPendientes();
        } else {
            Swal.fire('Error', res.message || 'Error al guardar el flete', 'error');
        }
    });
}

// ================================================================
// TAB 2: LISTADO DE FACTURAS
// ================================================================
function cargarListado() {
    document.getElementById('listadoLoading').style.display = 'block';
    document.getElementById('listadoContainer').innerHTML = '';
    document.getElementById('listadoEmpty').style.display = 'none';

    fetch('{{ url("api/facturacion/listado") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('listadoLoading').style.display = 'none';
        if (!res.success || !res.data) {
            document.getElementById('listadoEmpty').innerHTML = '<i class="fas fa-file-invoice" style="font-size:64px;opacity:.2;"></i><h3 class="mt-3 fw-bold text-danger">ERROR AL CARGAR FACTURAS</h3>';
            document.getElementById('listadoEmpty').style.display = 'block';
            return;
        }
        if (res.data.length === 0) {
            document.getElementById('listadoEmpty').style.display = 'block';
            return;
        }
        document.getElementById('listadoContainer').innerHTML = res.data.map(f => renderFacturaCard(f)).join('');
    })
    .catch(() => {
        document.getElementById('listadoLoading').style.display = 'none';
        document.getElementById('listadoEmpty').innerHTML = '<i class="fas fa-file-invoice" style="font-size:64px;opacity:.2;"></i><h3 class="mt-3 fw-bold text-danger">ERROR DE CONEXIÓN</h3>';
        document.getElementById('listadoEmpty').style.display = 'block';
    });
}

function renderFacturaCard(f) {
    const esCobrado = f.estado_factura === 'COBRADO';
    const badgeBg = esCobrado ? 'var(--mint)' : 'var(--sun-soft)';
    const badgeColor = esCobrado ? 'var(--success)' : 'var(--ink)';
    const badgeLabel = esCobrado ? 'COBRADO' : 'POR COBRAR';
    // Tipo de cambio con el que se emitieron los fletes de la factura: si
    // agrupa dias con cambio distinto se muestra el rango.
    let tcTexto = '—';
    if (f.tipo_cambio_min != null && f.tipo_cambio_max != null) {
        tcTexto = parseFloat(f.tipo_cambio_min).toFixed(2) === parseFloat(f.tipo_cambio_max).toFixed(2)
            ? 'Bs ' + parseFloat(f.tipo_cambio_min).toFixed(2)
            : 'Bs ' + parseFloat(f.tipo_cambio_min).toFixed(2) + ' - ' + parseFloat(f.tipo_cambio_max).toFixed(2);
    }
    return `<div class="factura-card">
        <div class="factura-header p-3 d-flex flex-wrap justify-content-between align-items-center" onclick="toggleDetalle('${f.numero_factura}')">
            <div class="d-flex flex-wrap gap-4 align-items-center">
                <div><small class="fw-bold">FECHA</small><p class="mb-0 fw-bold fs-6">${f.fecha_factura || '—'}</p></div>
                <div><small class="fw-bold">N° FACTURA</small><p class="mb-0 fw-bold font-monospace">${f.numero_factura}</p></div>
                <div><small class="fw-bold">CLIENTE</small><p class="mb-0 fw-bold">${f.cliente_nombre || '—'}</p></div>
                <div><small class="fw-bold">MONTO FACTURADO</small><p class="mb-0 fw-bold fs-5" style="color:var(--success);">${formatCurrency(f.total_monto)}</p></div>
                <div><small class="fw-bold">TIPO CAMBIO</small><p class="mb-0 fw-bold font-monospace">${tcTexto}</p></div>
                <div><small class="fw-bold">ESTADO</small><p class="mb-0"><span class="badge fw-bold px-3 py-2" style="border:2px solid var(--line-strong);font-size:.9rem;background:${badgeBg};color:${badgeColor};">${badgeLabel}</span></p></div>
                <div><small class="fw-bold">FLETES</small><p class="mb-0 fw-bold">${f.cantidad_fletes} flete(s)</p></div>
            </div>
            <div class="d-flex gap-2 mt-2 mt-md-0">
                <button class="btn btn-sm fw-bold d-flex align-items-center gap-1" style="border:3px solid var(--line-strong);background: var(--primary);color:#fff;padding:8px 14px;" onclick="event.stopPropagation();toggleDetalle('${f.numero_factura}')"><i class="fas fa-eye"></i> VER FLETES</button>
                <button class="btn btn-sm fw-bold d-flex align-items-center gap-1" style="border:3px solid var(--line-strong);padding:8px 14px;background:${esCobrado ? 'var(--sun-soft)' : 'var(--mint)'};color: var(--ink);" onclick="event.stopPropagation();toggleCobrado('${f.numero_factura}','${f.estado_factura}')">
                    <i class="fas ${esCobrado ? 'fa-undo' : 'fa-check-circle'}"></i> ${esCobrado ? 'MARCAR POR COBRAR' : 'MARCAR COBRADO'}
                </button>
            </div>
        </div>
        <div class="detalle-fletes" id="detalle_${f.numero_factura}" style="display:none;background:var(--surface);">
            <div class="text-center py-3" id="loading_${f.numero_factura}"><div class="spinner-border spinner-border-sm text-dark" role="status"></div></div>
            <div class="table-responsive" id="tabla_${f.numero_factura}" style="display:none;"></div>
        </div>
    </div>`;
}

let expandedFactura = null;

function toggleDetalle(numeroFactura) {
    const detalle = document.getElementById('detalle_' + numeroFactura);
    if (expandedFactura === numeroFactura) { detalle.style.display = 'none'; expandedFactura = null; return; }
    if (expandedFactura) { const p = document.getElementById('detalle_' + expandedFactura); if (p) p.style.display = 'none'; }
    expandedFactura = numeroFactura;
    detalle.style.display = 'block';
    document.getElementById('loading_' + numeroFactura).style.display = 'block';
    document.getElementById('tabla_' + numeroFactura).style.display = 'none';
    fetch('{{ url("api/facturacion/fletes") }}/' + numeroFactura, { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(res => {
        document.getElementById('loading_' + numeroFactura).style.display = 'none';
        if (!res.success || !res.data) return;
        const tabla = document.getElementById('tabla_' + numeroFactura);
        if (res.data.length === 0) { tabla.innerHTML = '<div class="text-center py-3">No se encontraron fletes</div>'; }
        else {
            tabla.innerHTML = `<table class="table mb-0" style="font-size:.95rem;">
                <thead><tr><th class="fw-bold" style="background:var(--line);border-bottom:2px solid var(--line-strong);padding:10px;">FECHA</th><th class="fw-bold" style="background:var(--line);border-bottom:2px solid var(--line-strong);padding:10px;">N° DOC</th><th class="fw-bold" style="background:var(--line);border-bottom:2px solid var(--line-strong);padding:10px;">UNIDAD</th><th class="fw-bold" style="background:var(--line);border-bottom:2px solid var(--line-strong);padding:10px;">RUTA</th><th class="fw-bold" style="background:var(--line);border-bottom:2px solid var(--line-strong);padding:10px;">MONTO</th><th class="fw-bold" style="background:var(--line);border-bottom:2px solid var(--line-strong);padding:10px;">TIPO CAMBIO</th><th class="fw-bold" style="background:var(--line);border-bottom:2px solid var(--line-strong);padding:10px;">TONELADAS</th><th class="fw-bold" style="background:var(--line);border-bottom:2px solid var(--line-strong);padding:10px;">CHOFER</th></tr></thead>
                <tbody>${res.data.map(fl => `<tr><td style="padding:10px;">${fl.fecha_ingreso}</td><td style="padding:10px;"><span class="font-monospace fw-bold">${fl.nro_documento || '—'}</span></td><td style="padding:10px;">${fl.placa_vehiculo || '—'}</td><td style="padding:10px;">${getRuta(fl)}</td><td style="padding:10px;" class="fw-bold">${formatCurrency(fl.monto)}</td><td style="padding:10px;" class="fw-bold font-monospace">${fl.tipo_cambio ? 'Bs ' + parseFloat(fl.tipo_cambio).toFixed(2) : '—'}</td><td style="padding:10px;">${fl.toneladas || '—'}</td><td style="padding:10px;">${fl.conductor_asignado || '—'}</td></tr>`).join('')}</tbody>
            </table>`;
        }
        tabla.style.display = 'block';
    });
}

function toggleCobrado(numeroFactura, estadoActual) {
    const nuevoEstado = estadoActual === 'COBRADO' ? 'FACTURADA' : 'COBRADO';
    fetch('{{ url("api/facturacion/cobrar") }}', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ numero_factura: numeroFactura, estado: nuevoEstado })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: `Factura marcada como ${nuevoEstado === 'COBRADO' ? 'COBRADO' : 'POR COBRAR'}`, timer: 1200, showConfirmButton: false });
            cargarListado();
        }
    });
}

// ================================================================
// TAB 3: FACTURACIÓN (batch)
// ================================================================
function cargarPendientes() {
    seleccionadas.clear();
    const params = new URLSearchParams();
    params.append('page', '1'); params.append('limit', '100'); params.append('estado', 'PENDIENTE');
    const fi = document.getElementById('filterFechaInicio').value;
    const ff = document.getElementById('filterFechaFin').value;
    const fc = document.getElementById('filterCliente').value;
    if (fi) params.append('fecha_inicio', fi);
    if (ff) params.append('fecha_fin', ff);
    if (fc) params.append('cliente', fc);
    document.getElementById('pendientesLoading').style.display = 'block';
    document.getElementById('pendientesContainer').innerHTML = '';
    actualizarSelectionBar();
    fetch('{{ url("api/facturacion/pendientes") }}?' + params.toString(), { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(res => {
        document.getElementById('pendientesLoading').style.display = 'none';
        if (!res.success || !res.data) {
            document.getElementById('pendientesContainer').innerHTML = '<div class="text-center py-5 fw-bold fs-5 text-danger" style="border:4px solid var(--line);">ERROR AL CARGAR PENDIENTES</div>';
            return;
        }
        pendientesData = res.data;
        renderPendientes();
    })
    .catch(() => {
        document.getElementById('pendientesLoading').style.display = 'none';
        document.getElementById('pendientesContainer').innerHTML = '<div class="text-center py-5 fw-bold fs-5 text-danger" style="border:4px solid var(--line);">ERROR DE CONEXIÓN</div>';
    });
}

function renderPendientes() {
    const container = document.getElementById('pendientesContainer');
    if (pendientesData.length === 0) { container.innerHTML = '<div class="text-center py-5 fw-bold fs-5" style="border:4px solid var(--line);">NO HAY FLETES PENDIENTES DE FACTURACIÓN</div>'; return; }
    let html = `<div style="border:4px solid var(--line);"><table class="table-excel mb-0" style="font-size:.95rem;"><thead><tr>
        <th style="padding:12px 8px;width:50px;"><input type="checkbox" class="form-check-input" style="border:2px solid var(--line-strong);width:20px;height:20px;cursor:pointer;" onchange="toggleSelectAll(this)"></th>
        <th class="text-center" style="padding:12px 8px;">FECHA</th><th class="text-center" style="padding:12px 8px;">N° DOC</th><th class="text-center" style="padding:12px 8px;">UNIDAD</th>
        <th style="padding:12px 8px;">RUTA / CONCEPTO</th><th class="text-center" style="padding:12px 8px;">MONTO</th><th class="text-center" style="padding:12px 8px;">CHOFER</th>
    </tr></thead><tbody>`;
    html += pendientesData.map(p => `<tr style="background:${seleccionadas.has(p.id_ingreso) ? 'var(--sun-soft)' : '#fff'};">
        <td style="padding:10px 8px;text-align:center;"><input type="checkbox" class="form-check-input row-checkbox" style="border:2px solid var(--line-strong);width:20px;height:20px;cursor:pointer;" data-id="${p.id_ingreso}" ${seleccionadas.has(p.id_ingreso) ? 'checked' : ''} onchange="toggleSeleccion(${p.id_ingreso}, this)"></td>
        <td class="text-center fw-bold" style="padding:10px 8px;">${p.fecha_ingreso}</td>
        <td class="text-center fw-bold font-monospace" style="padding:10px 8px;">${p.nro_documento || '—'}</td>
        <td class="text-center fw-bold" style="padding:10px 8px;">${p.placa_vehiculo || '—'}</td>
        <td class="fw-bold" style="padding:10px 8px;max-width:250px;">${getRuta(p)}</td>
        <td class="text-center fw-bold" style="padding:10px 8px;color:var(--success);">${formatCurrency(p.monto)}</td>
        <td class="text-center" style="padding:10px 8px;">${p.chofer || '—'}</td>
    </tr>`).join('');
    html += `</tbody></table></div>`;
    container.innerHTML = html;
    actualizarSelectionBar();
}

function toggleSeleccion(id, checkbox) {
    if (checkbox.checked) seleccionadas.add(id); else seleccionadas.delete(id);
    renderPendientes();
}

function toggleSelectAll(checkbox) {
    pendientesData.forEach(p => { if (checkbox.checked) seleccionadas.add(p.id_ingreso); else seleccionadas.delete(p.id_ingreso); });
    renderPendientes();
}

function actualizarSelectionBar() {
    const count = seleccionadas.size;
    document.getElementById('selectionBar').style.display = count > 0 ? 'flex' : 'none';
    document.getElementById('selectedCount').textContent = count + ' seleccionado(s)';
    let total = 0;
    pendientesData.forEach(p => { if (seleccionadas.has(p.id_ingreso)) total += parseFloat(p.monto || 0); });
    document.getElementById('selectedTotal').textContent = 'Total: ' + formatCurrency(total);
}

function abrirModalFacturar() {
    if (seleccionadas.size === 0) { Swal.fire('Seleccione registros', 'Debe seleccionar al menos un flete para facturar', 'warning'); return; }
    let total = 0;
    pendientesData.forEach(p => { if (seleccionadas.has(p.id_ingreso)) total += parseFloat(p.monto || 0); });
    document.getElementById('modalCount').textContent = seleccionadas.size;
    document.getElementById('modalTotal').textContent = 'TOTAL: ' + formatCurrency(total);
    document.getElementById('modalFacturar').style.display = 'flex';
}

function cerrarModalFacturar() {
    document.getElementById('modalFacturar').style.display = 'none';
}

function confirmarBatchFacturar() {
    const numeroFactura = document.getElementById('modalNumeroFactura').value.trim();
    const clienteNombre = document.getElementById('modalClienteNombre').value.trim();
    if (!numeroFactura) { Swal.fire('Requerido', 'El número de factura es obligatorio', 'warning'); return; }
    if (!clienteNombre) { Swal.fire('Requerido', 'La razón social es obligatoria', 'warning'); return; }
    document.getElementById('btnConfirmarFactura').disabled = true;
    document.getElementById('btnConfirmarFactura').innerHTML = '<i class="fas fa-spinner fa-spin"></i> FACTURANDO...';
    fetch('{{ url("api/facturacion/batch-facturar") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({
            ids: Array.from(seleccionadas),
            numero_factura: numeroFactura,
            fecha_factura: document.getElementById('modalFechaFactura').value,
            cliente_nombre: clienteNombre,
        })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('btnConfirmarFactura').disabled = false;
        document.getElementById('btnConfirmarFactura').innerHTML = '<i class="fas fa-check"></i> CONFIRMAR FACTURA';
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'FACTURADO', text: res.message, timer: 2000, showConfirmButton: false });
            document.getElementById('modalFacturar').style.display = 'none';
            document.getElementById('modalNumeroFactura').value = '';
            document.getElementById('modalClienteNombre').value = '';
            seleccionadas.clear();
            cargarListado(); cargarPendientes(); cargarFletes();
        } else { Swal.fire('Error', res.message || 'Error al facturar', 'error'); }
    });
}

function formatCurrency(val) {
    return 'Bs. ' + parseFloat(val || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function getRuta(f) {
    const o = f.origen || ''; const d = f.destino || '';
    return (o || d) ? o + ' - ' + d : (f.concepto || '—');
}
</script>
@endpush
