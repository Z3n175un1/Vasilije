@extends('layouts.master')

@section('title', 'Facturación - VASILIJE')

@push('styles')
<style>
.factura-card {
    border: 4px solid #000;
    margin-bottom: 1rem;
    background: #fff;
}
.factura-header {
    border-bottom: 2px solid #000;
    cursor: pointer;
    transition: background 0.2s;
}
.factura-header:hover {
    background: #fff8e1;
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
    background: #000 !important;
    color: #ffc107 !important;
}
.tab-btn:not(.active) {
    background: #fff;
    color: #000;
    border-right: 4px solid #000;
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
    @if(session('success'))
        <div class="alert alert-success font-bold text-center mb-4" style="border:3px solid #000;border-radius:0;">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">FACTURACIÓN</h1>
            <p class="font-bold small text-black uppercase">Control de Ingresos y Facturación de Fletes</p>
        </div>
    </header>

    <!-- TABS -->
    <div class="d-flex mb-4" style="border:4px solid #000;">
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
            <button class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 hover-scale" onclick="abrirModalNuevoFlete()">
                <i class="fas fa-plus me-1"></i> NUEVO FLETE
            </button>
        </div>

        <!-- Filtros fletes -->
        <div class="p-3 mb-3" style="background:#ffc107;border:4px solid #000;">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">FECHA INICIO</label>
                    <input type="date" class="form-control fw-bold" id="filtroFletesInicio" style="border-radius:0;border:3px solid #000;padding:8px;">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">FECHA FIN</label>
                    <input type="date" class="form-control fw-bold" id="filtroFletesFin" style="border-radius:0;border:3px solid #000;padding:8px;">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">ESTADO</label>
                    <select class="form-control fw-bold" id="filtroFletesEstado" style="border-radius:0;border:3px solid #000;padding:8px;">
                        <option value="">TODOS</option>
                        <option value="PENDIENTE">PENDIENTE</option>
                        <option value="FACTURADA">FACTURADA</option>
                        <option value="COBRADO">COBRADO</option>
                        <option value="ANULADA">ANULADA</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn fw-bold w-100" style="border:3px solid #000;background:#000;color:#ffc107;padding:8px;" onclick="cargarFletes()">
                        <i class="fas fa-search"></i> FILTRAR
                    </button>
                </div>
            </div>
        </div>

        <div id="fletesLoading" class="text-center py-5" style="display:none;">
            <div class="spinner-border text-dark" role="status"></div>
        </div>
        <div id="fletesEmpty" class="text-center py-5" style="display:none;border:4px solid #000;">
            <i class="fas fa-truck" style="font-size:64px;opacity:.2;"></i>
            <h3 class="mt-3 fw-bold">NO HAY FLETES REGISTRADOS</h3>
        </div>
        <div id="fletesContainer" style="display:none;">
            <div style="border:4px solid #000;overflow:hidden;">
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
        <div class="p-3 mb-4" style="background:#ffc107;border:4px solid #000;">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">FECHA INICIO</label>
                    <input type="date" class="form-control fw-bold" id="filterFechaInicio" style="border-radius:0;border:3px solid #000;padding:10px;">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">FECHA FIN</label>
                    <input type="date" class="form-control fw-bold" id="filterFechaFin" style="border-radius:0;border:3px solid #000;padding:10px;">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold d-block mb-1 small">CLIENTE</label>
                    <input type="text" class="form-control fw-bold" id="filterCliente" style="border-radius:0;border:3px solid #000;padding:10px;" placeholder="Buscar cliente...">
                </div>
                <div class="col-md-3">
                    <button class="btn fw-bold w-100" style="border:3px solid #000;background:#000;color:#ffc107;padding:10px;" onclick="cargarPendientes()">
                        <i class="fas fa-search"></i> FILTRAR
                    </button>
                </div>
            </div>
        </div>
        <div id="selectionBar" class="d-flex justify-content-between align-items-center mb-3 p-3" style="background:#fff;border:4px solid #000;display:none!important;">
            <div class="d-flex align-items-center gap-3">
                <span class="fw-bold fs-5" id="selectedCount">0 seleccionado(s)</span>
                <span class="fw-bold fs-5" style="color:#007400;" id="selectedTotal">Total: Bs. 0.00</span>
            </div>
            <button class="btn fw-bold d-flex align-items-center gap-2" style="background:#000;color:#ffc107;border:4px solid #000;padding:12px 24px;font-size:1rem;" onclick="abrirModalFacturar()">
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
        <div class="p-3" style="background:#000;color:#ffc107;display:flex;justify-content:space-between;align-items:center;">
            <h3 class="mb-0 fw-bold fs-5"><i class="fas fa-plus-circle me-2"></i> NUEVO FLETE (INGRESO)</h3>
            <button class="btn btn-sm text-white fw-bold" onclick="cerrarModalNuevoFlete()" style="font-size:1.5rem;line-height:1;">×</button>
        </div>
        <div class="p-3" style="background:#fff;border:4px solid #000;border-top:none;">
            <form id="formNuevoFlete" onsubmit="return guardarFlete(event)">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">UNIDAD <span class="text-danger">*</span></label>
                        <select class="form-control fw-bold" id="nf_id_vehiculo" style="border-radius:0;border:3px solid #000;padding:10px;" required>
                            <option value="">SELECCIONE...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">CONDUCTOR</label>
                        <select class="form-control fw-bold" id="nf_id_personal" style="border-radius:0;border:3px solid #000;padding:10px;">
                            <option value="">SELECCIONE...</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">CLIENTE / RAZÓN SOCIAL</label>
                        <input type="text" class="form-control fw-bold" id="nf_cliente_nombre" style="border-radius:0;border:3px solid #000;padding:10px;" placeholder="Nombre del cliente">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">MONTO (Bs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="nf_monto" style="border-radius:0;border:3px solid #000;padding:10px;" required min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="fw-bold small text-uppercase">RUTA</label>
                        <select class="form-control fw-bold" id="nf_id_tramo" style="border-radius:0;border:3px solid #000;padding:10px;" onchange="seleccionarRutaNF(this)">
                            <option value="">SELECCIONE RUTA...</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="fw-bold small text-uppercase">ORIGEN</label>
                        <input type="text" class="form-control fw-bold" id="nf_origen" style="border-radius:0;border:3px solid #000;padding:10px;" placeholder="Ciudad origen">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small text-uppercase">DESTINO</label>
                        <input type="text" class="form-control fw-bold" id="nf_destino" style="border-radius:0;border:3px solid #000;padding:10px;" placeholder="Ciudad destino">
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold small text-uppercase">TON.</label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="nf_toneladas" style="border-radius:0;border:3px solid #000;padding:10px;" min="0" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold small text-uppercase">KM</label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="nf_kilometraje" style="border-radius:0;border:3px solid #000;padding:10px;" min="0" value="0">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">CONCEPTO <span class="text-danger">*</span></label>
                        <input type="text" class="form-control fw-bold" id="nf_concepto" style="border-radius:0;border:3px solid #000;padding:10px;" required placeholder="Descripción del flete">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold small text-uppercase">FECHA</label>
                        <input type="date" class="form-control fw-bold" id="nf_fecha_ingreso" style="border-radius:0;border:3px solid #000;padding:10px;">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold small text-uppercase">TIPO PAGO</label>
                        <select class="form-control fw-bold" id="nf_tipo_pago" style="border-radius:0;border:3px solid #000;padding:10px;">
                            <option value="EFECTIVO">EFECTIVO</option>
                            <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                            <option value="CHEQUE">CHEQUE</option>
                            <option value="OTRO">OTRO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="fw-bold small text-uppercase">OBSERVACIONES</label>
                    <textarea class="form-control fw-bold" id="nf_observaciones" rows="2" style="border-radius:0;border:3px solid #000;padding:10px;" placeholder="Observaciones..."></textarea>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2" style="background:#000;color:#ffc107;border:4px solid #000;padding:12px;font-size:1rem;" id="btnGuardarFlete">
                        <i class="fas fa-save"></i> GUARDAR FLETE
                    </button>
                    <button type="button" class="btn fw-bold" style="border:4px solid #000;padding:12px;font-size:1rem;" onclick="cerrarModalNuevoFlete()">CANCELAR</button>
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
        <div class="p-4" style="background:#000;color:#ffc107;">
            <h3 class="mb-0 fw-bold fs-4"><i class="fas fa-file-invoice me-2"></i> FACTURAR <span id="modalCount">0</span> FLETE(S)</h3>
        </div>
        <div class="p-4" style="background:#fff;border:4px solid #000;border-top:none;">
            <div class="row g-3">
                <div class="col-12">
                    <label class="fw-bold text-uppercase small">FECHA FACTURA</label>
                    <input type="date" class="form-control fw-bold" id="modalFechaFactura" style="border-radius:0;border:3px solid #000;padding:12px;font-size:1rem;">
                </div>
                <div class="col-12">
                    <label class="fw-bold text-uppercase small">NÚMERO DE FACTURA <span class="text-danger">*</span></label>
                    <input type="text" class="form-control fw-bold" id="modalNumeroFactura" style="border-radius:0;border:3px solid #000;padding:12px;font-size:1rem;" placeholder="Ej: 123">
                </div>
                <div class="col-12">
                    <label class="fw-bold text-uppercase small">RAZÓN SOCIAL <span class="text-danger">*</span></label>
                    <input type="text" class="form-control fw-bold" id="modalClienteNombre" style="border-radius:0;border:3px solid #000;padding:12px;font-size:1rem;" placeholder="Nombre del cliente">
                </div>
                <div class="col-12">
                    <div class="p-3 fw-bold fs-5 text-center" style="background:#ffc107;border:3px solid #000;" id="modalTotal">TOTAL: Bs. 0.00</div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="btn fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2" style="background:#000;color:#ffc107;border:4px solid #000;padding:14px;font-size:1.1rem;" onclick="confirmarBatchFacturar()" id="btnConfirmarFactura">
                    <i class="fas fa-check"></i> CONFIRMAR FACTURA
                </button>
                <button class="btn fw-bold" style="border:4px solid #000;padding:14px;font-size:1.1rem;" onclick="cerrarModalFacturar()">CANCELAR</button>
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

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('nf_fecha_ingreso').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalFechaFactura').value = new Date().toISOString().split('T')[0];
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
            selT.innerHTML = '<option value="">SELECCIONE RUTA...</option>' +
                tramosList.map(t => `<option value="${t.id_tramo}">${t.origen} → ${t.destino} (Bs. ${t.precio_total})</option>`).join('');
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
    if (tab === 'facturacion') actualizarSelectionBar();
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
        if (!res.success || !res.data || res.data.length === 0) {
            document.getElementById('fletesEmpty').style.display = 'block';
            return;
        }
        document.getElementById('fletesContainer').style.display = 'block';
        const tbody = document.getElementById('fletesBody');
        tbody.innerHTML = res.data.map(f => {
            const estadoColor = f.estado_factura === 'COBRADO' ? '#007400' :
                f.estado_factura === 'FACTURADA' ? '#856404' :
                f.estado_factura === 'ANULADA' ? '#740000' : '#000';
            const estadoBg = f.estado_factura === 'COBRADO' ? '#e2ffd6' :
                f.estado_factura === 'FACTURADA' ? '#fff3cd' :
                f.estado_factura === 'ANULADA' ? '#ffdcd6' : '#f0f0f0';
            const ruta = (f.origen || '') + (f.destino ? ' - ' + f.destino : '');
            return `<tr>
                <td class="fw-bold" style="white-space:nowrap;">${f.fecha_ingreso || '—'}</td>
                <td class="fw-bold font-monospace">${f.nro_documento || '—'}</td>
                <td class="fw-bold">${f.placa_vehiculo || '—'}</td>
                <td>${f.cliente_nombre || '—'}</td>
                <td>${ruta || '—'}</td>
                <td>${f.concepto || '—'}</td>
                <td class="fw-bold" style="color:#007400;">${formatCurrency(f.monto)}</td>
                <td><span class="badge fw-bold px-2 py-1" style="border:2px solid #000;background:${estadoBg};color:${estadoColor};">${f.estado_factura || '—'}</span></td>
                <td>
                    <div class="d-flex gap-1 justify-content-center">
                        <button class="btn btn-sm btn-warning border-black fw-bold" onclick="editarFlete(${f.id_ingreso})" title="EDITAR"><i class="fas fa-edit"></i></button>
                        ${f.estado_factura !== 'ANULADA' ? `<button class="btn btn-sm btn-danger border-black fw-bold" onclick="anularFlete(${f.id_ingreso})" title="ANULAR"><i class="fas fa-ban"></i></button>` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('');
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
        confirmButtonColor: '#dc3545',
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

function seleccionarRutaNF(select) {
    const id = parseInt(select.value);
    const t = tramosList.find(x => x.id_tramo === id);
    if (t) {
        document.getElementById('nf_origen').value = t.origen || '';
        document.getElementById('nf_destino').value = t.destino || '';
        document.getElementById('nf_monto').value = t.precio_total || '';
    }
}

// --- Modal Nuevo Flete ---
function abrirModalNuevoFlete() {
    document.getElementById('formNuevoFlete').reset();
    document.getElementById('nf_fecha_ingreso').value = new Date().toISOString().split('T')[0];
    document.getElementById('nf_monto').value = '';
    document.getElementById('nf_toneladas').value = '0';
    document.getElementById('nf_kilometraje').value = '0';
    const selR = document.getElementById('nf_id_tramo');
    if (selR) selR.value = '';
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
        cliente_nombre: document.getElementById('nf_cliente_nombre').value,
        monto: document.getElementById('nf_monto').value,
        origen: document.getElementById('nf_origen').value,
        destino: document.getElementById('nf_destino').value,
        toneladas: document.getElementById('nf_toneladas').value || 0,
        kilometraje_conducido: document.getElementById('nf_kilometraje').value || 0,
        concepto: document.getElementById('nf_concepto').value,
        fecha_ingreso: document.getElementById('nf_fecha_ingreso').value,
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
    .then(r => r.json().catch(() => ({ success: false, message: 'Error de conexión' })))
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
        if (!res.success || !res.data || res.data.length === 0) {
            document.getElementById('listadoEmpty').style.display = 'block';
            return;
        }
        document.getElementById('listadoContainer').innerHTML = res.data.map(f => renderFacturaCard(f)).join('');
    });
}

function renderFacturaCard(f) {
    const esCobrado = f.estado_factura === 'COBRADO';
    const badgeBg = esCobrado ? '#d4edda' : '#fff3cd';
    const badgeColor = esCobrado ? '#155724' : '#856404';
    const badgeLabel = esCobrado ? 'COBRADO' : 'POR COBRAR';
    return `<div class="factura-card">
        <div class="factura-header p-3 d-flex flex-wrap justify-content-between align-items-center" onclick="toggleDetalle('${f.numero_factura}')">
            <div class="d-flex flex-wrap gap-4 align-items-center">
                <div><small class="fw-bold">FECHA</small><p class="mb-0 fw-bold fs-6">${f.fecha_factura || '—'}</p></div>
                <div><small class="fw-bold">N° FACTURA</small><p class="mb-0 fw-bold font-monospace">${f.numero_factura}</p></div>
                <div><small class="fw-bold">CLIENTE</small><p class="mb-0 fw-bold">${f.cliente_nombre || '—'}</p></div>
                <div><small class="fw-bold">MONTO FACTURADO</small><p class="mb-0 fw-bold fs-5" style="color:#007400;">${formatCurrency(f.total_monto)}</p></div>
                <div><small class="fw-bold">ESTADO</small><p class="mb-0"><span class="badge fw-bold px-3 py-2" style="border:2px solid #000;font-size:.9rem;background:${badgeBg};color:${badgeColor};">${badgeLabel}</span></p></div>
                <div><small class="fw-bold">FLETES</small><p class="mb-0 fw-bold">${f.cantidad_fletes} flete(s)</p></div>
            </div>
            <div class="d-flex gap-2 mt-2 mt-md-0">
                <button class="btn btn-sm fw-bold d-flex align-items-center gap-1" style="border:3px solid #000;background:#000;color:#ffc107;padding:8px 14px;" onclick="event.stopPropagation();toggleDetalle('${f.numero_factura}')"><i class="fas fa-eye"></i> VER FLETES</button>
                <button class="btn btn-sm fw-bold d-flex align-items-center gap-1" style="border:3px solid #000;padding:8px 14px;background:${esCobrado ? '#fff3cd' : '#d4edda'};color:#000;" onclick="event.stopPropagation();toggleCobrado('${f.numero_factura}','${f.estado_factura}')">
                    <i class="fas ${esCobrado ? 'fa-undo' : 'fa-check-circle'}"></i> ${esCobrado ? 'MARCAR POR COBRAR' : 'MARCAR COBRADO'}
                </button>
            </div>
        </div>
        <div class="detalle-fletes" id="detalle_${f.numero_factura}" style="display:none;background:#f8f9fa;">
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
                <thead><tr><th class="fw-bold" style="background:#e9ecef;border-bottom:2px solid #000;padding:10px;">FECHA</th><th class="fw-bold" style="background:#e9ecef;border-bottom:2px solid #000;padding:10px;">N° DOC</th><th class="fw-bold" style="background:#e9ecef;border-bottom:2px solid #000;padding:10px;">UNIDAD</th><th class="fw-bold" style="background:#e9ecef;border-bottom:2px solid #000;padding:10px;">RUTA</th><th class="fw-bold" style="background:#e9ecef;border-bottom:2px solid #000;padding:10px;">MONTO</th><th class="fw-bold" style="background:#e9ecef;border-bottom:2px solid #000;padding:10px;">CHOFER</th></tr></thead>
                <tbody>${res.data.map(fl => `<tr><td style="padding:10px;">${fl.fecha_ingreso}</td><td style="padding:10px;"><span class="font-monospace fw-bold">${fl.nro_documento || '—'}</span></td><td style="padding:10px;">${fl.placa_vehiculo || '—'}</td><td style="padding:10px;">${getRuta(fl)}</td><td style="padding:10px;" class="fw-bold">${formatCurrency(fl.monto)}</td><td style="padding:10px;">${fl.conductor_asignado || '—'}</td></tr>`).join('')}</tbody>
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
    ['filterFechaInicio','filterFechaFin','filterCliente'].forEach(id => {
        const v = document.getElementById(id).value;
        if (v) params.append(id.replace('filter','').toLowerCase(), v);
    });
    document.getElementById('pendientesLoading').style.display = 'block';
    document.getElementById('pendientesContainer').innerHTML = '';
    actualizarSelectionBar();
    fetch('{{ url("api/facturacion/pendientes") }}?' + params.toString(), { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(res => {
        document.getElementById('pendientesLoading').style.display = 'none';
        if (!res.success) return;
        pendientesData = res.data || [];
        renderPendientes();
    });
}

function renderPendientes() {
    const container = document.getElementById('pendientesContainer');
    if (pendientesData.length === 0) { container.innerHTML = '<div class="text-center py-5 fw-bold fs-5" style="border:4px solid #000;">NO HAY FLETES PENDIENTES DE FACTURACIÓN</div>'; return; }
    let html = `<div style="border:4px solid #000;"><table class="table-excel mb-0" style="font-size:.95rem;"><thead><tr>
        <th style="padding:12px 8px;width:50px;"><input type="checkbox" class="form-check-input" style="border:2px solid #000;width:20px;height:20px;cursor:pointer;" onchange="toggleSelectAll(this)"></th>
        <th class="text-center" style="padding:12px 8px;">FECHA</th><th class="text-center" style="padding:12px 8px;">N° DOC</th><th class="text-center" style="padding:12px 8px;">UNIDAD</th>
        <th style="padding:12px 8px;">RUTA / CONCEPTO</th><th class="text-center" style="padding:12px 8px;">MONTO</th><th class="text-center" style="padding:12px 8px;">CHOFER</th>
    </tr></thead><tbody>`;
    html += pendientesData.map(p => `<tr style="background:${seleccionadas.has(p.id_ingreso) ? '#fffde7' : '#fff'};">
        <td style="padding:10px 8px;text-align:center;"><input type="checkbox" class="form-check-input row-checkbox" style="border:2px solid #000;width:20px;height:20px;cursor:pointer;" data-id="${p.id_ingreso}" ${seleccionadas.has(p.id_ingreso) ? 'checked' : ''} onchange="toggleSeleccion(${p.id_ingreso}, this)"></td>
        <td class="text-center fw-bold" style="padding:10px 8px;">${p.fecha_ingreso}</td>
        <td class="text-center fw-bold font-monospace" style="padding:10px 8px;">${p.nro_documento || '—'}</td>
        <td class="text-center fw-bold" style="padding:10px 8px;">${p.placa_vehiculo || '—'}</td>
        <td class="fw-bold" style="padding:10px 8px;max-width:250px;">${getRuta(p)}</td>
        <td class="text-center fw-bold" style="padding:10px 8px;color:#007400;">${formatCurrency(p.monto)}</td>
        <td class="text-center" style="padding:10px 8px;">${p.chofer || p.conductor_asignado || '—'}</td>
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
