@extends('layouts.master')

@section('title', 'Panel de Control - VASILIJE')

@push('styles')
<style>
#reporteTableDash tbody tr:not([id^="dash-detalle-"]):hover { background: #f0f0f0 !important; }
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
.modal-overlay-fact .form-control {
    font-size: 0.9rem;
}
</style>
@endpush

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy animate-slide-up">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">PANEL DE CONTROL</h1>
            <p class="font-bold small text-black uppercase">Monitoreo de Flota y Control Operativo</p>
        </div>
        <a href="{{ route('vehiculos.create') }}" class="btn-bento btn-bento-primary border-black py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none hover-scale btn-press">
            <i class="fas fa-plus me-1"></i> NUEVA UNIDAD
        </a>
    </header>

    <div id="dashboardLoading" class="text-center py-5">
        <h2 class="font-bold fs-mid">CONECTANDO...</h2>
    </div>

    <div id="dashboardContent" class="scroll-content" style="display:none;">
        <div class="d-flex flex-wrap gap-2 mb-3" id="estadoFilter">
            <button class="btn font-bold uppercase btn-filtro-activo" data-estado="1">ACTIVO</button>
            <button class="btn font-bold uppercase btn-filtro-inactivo" data-estado="2">MANTENIMIENTO</button>
            <button class="btn font-bold uppercase btn-filtro-inactivo" data-estado="3">VENDIDO</button>
            <button class="btn font-bold uppercase btn-filtro-inactivo" data-estado="">TODOS</button>
        </div>

        <div class="bento-card" style="padding: 0; overflow: hidden; border: 4px solid #000; box-shadow: 6px 6px 0px #000;">
            <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
                <span class="small uppercase font-bold text-black"><i class="fas fa-truck me-2"></i> Monitoreo de Flota Activa</span>
                <span class="badge bg-black text-white px-2 py-1 x-small font-bold" id="totalUnidades">TOTAL UNIDADES: 0</span>
            </div>

            <div class="table-responsive-brutalist">
                <table class="table-excel mb-0" id="vehiculosTable">
                    <thead>
                        <tr>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Placa</th>
                            <th class="text-center">Categoría</th>
                            <th class="text-center">Conductor</th>
                            <th class="text-center">Acciones</th>
                            <th class="text-center">TN</th>
                            <th class="text-center">Ingresos</th>
                            <th class="text-center">Egresos</th>
                            <th class="text-center">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody id="vehiculosBody">
                        <tr>
                            <td colspan="9" class="text-center py-5 opacity-50 small">CARGANDO DATOS...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="reporteModal" style="display:none; z-index: 10000;">
    <div class="bento-detail-modal" style="max-width: 1200px; border: 6px solid #000;">
        <div class="d-flex justify-content-between align-items-center no-print" style="background:#000;color:#ffc107;padding:18px 24px;">
            <div>
                <h3 class="mb-0 fw-bold fs-4"><i class="fas fa-file-invoice-dollar me-2"></i> REPORTE: <span id="reportePlaca"></span></h3>
                <p class="mb-0 small fw-bold text-white-50">Balance de Ingresos y Gastos</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm fw-bold" style="background:#ffc107;color:#000;border:2px solid #ffc107;padding:6px 14px;" onclick="window.print()">
                    <i class="fas fa-print"></i> IMPRIMIR
                </button>
                <button class="btn btn-sm fw-bold" style="background:transparent;color:#ffc107;border:2px solid #ffc107;padding:6px 14px;" onclick="cerrarReporte()">✕ CERRAR</button>
            </div>
        </div>
        <div class="detail-scroll" id="reporteContent" style="padding:20px 24px;">
        </div>
    </div>
</div>

<!-- MODAL REGISTRAR FLETE DESDE UNIDADES -->
<div class="modal-overlay-fact" id="modalFleteDashboard" style="display:none;z-index:9999;" onclick="if(event.target===this)cerrarModalFleteDash()">
    <div class="modal-content-fact" onclick="event.stopPropagation()" style="max-width:560px;width:95%;max-height:90vh;overflow-y:auto;">
        <div class="p-3" style="background:#000;color:#ffc107;display:flex;justify-content:space-between;align-items:center;">
            <h3 class="mb-0 fw-bold fs-5"><i class="fas fa-plus-circle me-2"></i> REGISTRAR FLETE</h3>
            <button class="btn btn-sm text-white fw-bold" onclick="cerrarModalFleteDash()" style="font-size:1.5rem;line-height:1;">×</button>
        </div>
        <div class="p-3" style="background:#fff;border:4px solid #000;border-top:none;">
            <form id="formFleteDash" onsubmit="return guardarFleteDash(event)">
                @csrf
                <div class="mb-3 p-2 text-center fw-bold fs-5" style="background:#ffc107;border:3px solid #000;" id="fleteDashUnidad">UNIDAD: —</div>
                <input type="hidden" id="fd_id_vehiculo_val">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">CONDUCTOR</label>
                        <select class="form-control fw-bold" id="fd_id_personal" style="border-radius:0;border:3px solid #000;padding:10px;">
                            <option value="">SELECCIONE...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">MONTO (Bs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="fd_monto" style="border-radius:0;border:3px solid #000;padding:10px;" required min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="fw-bold small text-uppercase">RUTA</label>
                        <select class="form-control fw-bold" id="fd_id_tramo" style="border-radius:0;border:3px solid #000;padding:10px;" onchange="seleccionarRuta(this)">
                            <option value="">SELECCIONE RUTA...</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">CLIENTE</label>
                        <input type="text" class="form-control fw-bold" id="fd_cliente_nombre" style="border-radius:0;border:3px solid #000;padding:10px;" placeholder="Nombre del cliente">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-uppercase">TIPO PAGO</label>
                        <select class="form-control fw-bold" id="fd_tipo_pago" style="border-radius:0;border:3px solid #000;padding:10px;">
                            <option value="EFECTIVO">EFECTIVO</option>
                            <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                            <option value="CHEQUE">CHEQUE</option>
                            <option value="OTRO">OTRO</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="fw-bold small text-uppercase">ORIGEN</label>
                        <input type="text" class="form-control fw-bold" id="fd_origen" style="border-radius:0;border:3px solid #000;padding:10px;" placeholder="Ciudad origen">
                    </div>
                    <div class="col-md-5">
                        <label class="fw-bold small text-uppercase">DESTINO</label>
                        <input type="text" class="form-control fw-bold" id="fd_destino" style="border-radius:0;border:3px solid #000;padding:10px;" placeholder="Ciudad destino">
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold small text-uppercase">TON.</label>
                        <input type="number" step="0.01" class="form-control fw-bold" id="fd_toneladas" style="border-radius:0;border:3px solid #000;padding:10px;" min="0" value="0">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="fw-bold small text-uppercase">CONCEPTO <span class="text-danger">*</span></label>
                        <input type="text" class="form-control fw-bold" id="fd_concepto" style="border-radius:0;border:3px solid #000;padding:10px;" required placeholder="Descripción del flete">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small text-uppercase">FECHA</label>
                        <input type="date" class="form-control fw-bold" id="fd_fecha_ingreso" style="border-radius:0;border:3px solid #000;padding:10px;">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2" style="background:#000;color:#ffc107;border:4px solid #000;padding:12px;font-size:1rem;" id="btnGuardarFleteDash">
                        <i class="fas fa-save"></i> GUARDAR FLETE
                    </button>
                    <button type="button" class="btn fw-bold" style="border:4px solid #000;padding:12px;font-size:1rem;" onclick="cerrarModalFleteDash()">CANCELAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let vehiculos = [];
let tramos = [];
let currentFilter = '1';

document.addEventListener('DOMContentLoaded', function() {
    loadVehiculos();
    loadTramos();

    document.getElementById('estadoFilter')?.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        currentFilter = btn.dataset.estado;
        document.querySelectorAll('#estadoFilter .btn').forEach(b => {
            b.className = 'btn font-bold uppercase btn-filtro-inactivo';
        });
        btn.className = 'btn font-bold uppercase btn-filtro-activo';
        loadVehiculos();
    });
});

function loadVehiculos() {
    const url = `{{ url('api/vehiculos') }}?estado=${currentFilter}`;
    fetch(url, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('dashboardLoading').style.display = 'none';
        document.getElementById('dashboardContent').style.display = 'block';
        if (res.success) {
            vehiculos = res.data || [];
            renderVehiculos(vehiculos);
        }
    })
    .catch(() => {
        document.getElementById('dashboardLoading').innerHTML = '<h2 class="font-bold fs-mid text-danger">ERROR DE CONEXIÓN</h2>';
    });
}

function renderVehiculos(data) {
    const tbody = document.getElementById('vehiculosBody');
    document.getElementById('totalUnidades').textContent = `TOTAL UNIDADES: ${data.length}`;

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-5 opacity-50 small">NO HAY VEHÍCULOS REGISTRADOS EN EL SISTEMA</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(v => {
        const estadoColors = {
            1: { dot: '#00ff00', bg: '#e2ffd6', text: '#007400', label: 'ACTIVO' },
            2: { dot: '#ffff00', bg: '#fffcd4', text: '#746700', label: 'MANTENIMIENTO' },
            3: { dot: '#ff0000', bg: '#ffdcd6', text: '#740000', label: 'VENDIDO' }
        };
        const ec = estadoColors[v.estado] || estadoColors[3];
        const diff = (v.total_ingresos || 0) - (v.total_gastos || 0);

        return `<tr>
            <td class="text-center cursor-pointer" onclick="filtrarPorEstado(${v.estado})">
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <span style="width:18px;height:18px;border:2px solid #000;display:inline-block;border-radius:50%;background:${ec.dot}"></span>
                    <span class="badge font-bold uppercase" style="background:${ec.bg};color:${ec.text};font-size:12px;padding:6px 14px;border:3px solid #000;">${ec.label}</span>
                </div>
            </td>
            <td class="text-center">
                <span class="badge bg-black text-white px-4 py-3 font-bold border border-white" style="letter-spacing:2px;font-family:monospace;font-size:1.25rem;">${v.placa_vehiculo}</span>
            </td>
            <td class="uppercase font-bold text-black fs-mid text-center">${v.tipo_vehiculo || '—'}</td>
            <td class="font-bold text-black fs-mid text-center"><i class="fas fa-user-tie me-2 text-warning"></i> ${v.conductor || 'SIN CONDUCTOR'}</td>
            <td class="text-center align-middle">
                <div class="d-flex gap-2 justify-content-center align-items-center">
                    <button class="btn-action-mini bg-success text-white border-black" onclick="mostrarOpcionesIngreso(${v.id_vehiculo})" title="INGRESO / VENTA" ${v.estado != 1 && v.estado != 2 ? 'disabled style="opacity:0.35"' : ''} style="width:40px;height:40px;border-width:2px;border-radius:0;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-dollar-sign" style="font-size:1rem;"></i>
                    </button>
                    <button class="btn-action-mini bg-danger text-white border-black" onclick="prepararGasto(${v.id_vehiculo})" title="REGISTRAR GASTO (-$)" ${v.estado != 1 && v.estado != 2 ? 'disabled style="opacity:0.35"' : ''} style="width:40px;height:40px;border-width:2px;border-radius:0;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-minus-circle" style="font-size:1rem;"></i>
                    </button>
                    <span style="width:2px;height:28px;background:#000;display:inline-block;"></span>
                    <button class="btn-action-mini bg-warning text-black border-black" onclick="editarVehiculo(${v.id_vehiculo})" title="EDITAR" style="width:40px;height:40px;border-width:2px;border-radius:0;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-pencil-alt" style="font-size:1rem;"></i>
                    </button>
                    <button class="btn-action-mini bg-white text-black border-black" onclick="abrirReporte(${v.id_vehiculo},'${v.placa_vehiculo}')" title="REPORTES" style="width:40px;height:40px;border-width:2px;border-radius:0;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-file-invoice-dollar text-warning" style="font-size:1rem;"></i>
                    </button>
                </div>
            </td>
            <td class="font-bold text-center">${v.capacidad || '—'}</td>
            <td class="font-bold text-center" style="color:#007400;">${formatCurrency(v.total_ingresos || 0)}</td>
            <td class="font-bold text-center" style="color:#740000;">${formatCurrency(v.total_gastos || 0)}</td>
            <td class="font-bold text-center" style="color:${diff >= 0 ? '#007400' : '#740000'}">${formatCurrency(diff)}</td>
        </tr>`;
    }).join('');
}

function loadTramos() {
    fetch('{{ url("api/tramos") }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) tramos = res.data || [];
        const sel = document.getElementById('fd_id_tramo');
        if (sel) {
            sel.innerHTML = '<option value="">SELECCIONE RUTA...</option>' +
                tramos.map(t => `<option value="${t.id_tramo}">${t.origen} → ${t.destino} (Bs. ${t.precio_total})</option>`).join('');
        }
    });
}

function seleccionarRuta(select) {
    const id = parseInt(select.value);
    const t = tramos.find(x => x.id_tramo === id);
    if (t) {
        document.getElementById('fd_origen').value = t.origen || '';
        document.getElementById('fd_destino').value = t.destino || '';
        document.getElementById('fd_monto').value = t.precio_total || '';
    }
}

function filtrarPorEstado(estado) {
    currentFilter = String(estado);
    document.querySelectorAll('#estadoFilter .btn').forEach(b => {
        b.className = 'btn font-bold uppercase ' + (b.dataset.estado === currentFilter ? 'btn-filtro-activo' : 'btn-filtro-inactivo');
    });
    loadVehiculos();
}

function prepararGasto(id) {
    window.location.href = `{{ url('gastos/crear') }}?id_vehiculo=${id}`;
}

function mostrarOpcionesIngreso(id) {
    const v = vehiculos.find(x => x.id_vehiculo == id);
    if (!v) return;
    Swal.fire({
        title: 'SELECCIONE OPCIÓN',
        html: `<div style="display:flex;flex-direction:column;gap:12px;padding:8px;">
            <button class="btn fw-bold py-3" style="border:4px solid #000;background:#fff3cd;font-size:1.1rem;border-radius:0;" onclick="prepararIngreso(${id});Swal.close()">
                <i class="fas fa-truck me-2 text-warning"></i> REGISTRAR FLETE (INGRESO)
            </button>
            <button class="btn fw-bold py-3" style="border:4px solid #000;background:#f8d7da;font-size:1.1rem;border-radius:0;" onclick="venderVehiculo(${id},'${v.placa_vehiculo}');Swal.close()">
                <i class="fas fa-handshake me-2 text-danger"></i> VENTA DE VEHÍCULO
            </button>
        </div>`,
        showConfirmButton: false,
        showCloseButton: true,
        width: 420,
    });
}

function venderVehiculo(id, placa) {
    Swal.fire({
        title: '¿MARCAR COMO VENDIDO?',
        html: `<div class="text-start">
            <p class="fw-bold mb-2">Unidad: <span class="text-danger">${placa}</span></p>
            <p class="mb-0 small">Se cambiará el estado a <strong>VENDIDO</strong> y no aparecerá en unidades activas.</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, MARCAR VENDIDO',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('{{ url("api/vehiculos/vender") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ id_vehiculo: id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'VEHÍCULO VENDIDO', text: `Unidad ${placa} marcada como VENDIDO`, timer: 1500, showConfirmButton: false });
                loadVehiculos();
            } else {
                Swal.fire('Error', res.message || 'Error al marcar como vendido', 'error');
            }
        });
    });
}

function prepararIngreso(id) {
    const v = vehiculos.find(x => x.id_vehiculo == id);
    if (!v) return;
    document.getElementById('fleteDashUnidad').textContent = 'UNIDAD: ' + (v.placa_vehiculo || '—');
    document.getElementById('fd_id_vehiculo_val').value = id;
    document.getElementById('fd_id_tramo').value = '';
    document.getElementById('fd_monto').value = '';
    document.getElementById('fd_cliente_nombre').value = '';
    document.getElementById('fd_origen').value = '';
    document.getElementById('fd_destino').value = '';
    document.getElementById('fd_toneladas').value = '0';
    document.getElementById('fd_concepto').value = '';
    document.getElementById('fd_fecha_ingreso').value = new Date().toISOString().split('T')[0];
    document.getElementById('fd_tipo_pago').value = 'EFECTIVO';

    fetch('{{ url("api/personal") }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        const sel = document.getElementById('fd_id_personal');
        if (res.success) {
            sel.innerHTML = '<option value="">SELECCIONE...</option>' +
                (res.data || []).filter(p => p.estado == 1).map(p =>
                    `<option value="${p.id_personal}">${p.nombres || ''} ${p.apellidos || ''}</option>`).join('');
        }
    });

    document.getElementById('modalFleteDashboard').style.display = 'flex';
}

function cerrarModalFleteDash() {
    document.getElementById('modalFleteDashboard').style.display = 'none';
}

function guardarFleteDash(event) {
    event.preventDefault();
    const data = {
        id_vehiculo: document.getElementById('fd_id_vehiculo_val').value,
        id_personal: document.getElementById('fd_id_personal').value || null,
        cliente_nombre: document.getElementById('fd_cliente_nombre').value,
        monto: document.getElementById('fd_monto').value,
        origen: document.getElementById('fd_origen').value,
        destino: document.getElementById('fd_destino').value,
        toneladas: document.getElementById('fd_toneladas').value || 0,
        concepto: document.getElementById('fd_concepto').value,
        fecha_ingreso: document.getElementById('fd_fecha_ingreso').value,
        tipo_pago: document.getElementById('fd_tipo_pago').value,
    };
    if (!data.monto || parseFloat(data.monto) <= 0) { Swal.fire('Requerido', 'El monto debe ser mayor a 0', 'warning'); return; }
    if (!data.concepto) { Swal.fire('Requerido', 'El concepto es obligatorio', 'warning'); return; }

    document.getElementById('btnGuardarFleteDash').disabled = true;
    document.getElementById('btnGuardarFleteDash').innerHTML = '<i class="fas fa-spinner fa-spin"></i> GUARDANDO...';

    fetch('{{ route("facturacion.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json().catch(() => ({ success: false, message: 'Error de conexión' })))
    .then(res => {
        document.getElementById('btnGuardarFleteDash').disabled = false;
        document.getElementById('btnGuardarFleteDash').innerHTML = '<i class="fas fa-save"></i> GUARDAR FLETE';
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'FLETE REGISTRADO', text: res.message || 'Ingreso registrado exitosamente', timer: 2000, showConfirmButton: false });
            cerrarModalFleteDash();
            loadVehiculos();
        } else {
            Swal.fire('Error', res.message || 'Error al guardar el flete', 'error');
        }
    });
}

function editarVehiculo(id) {
    window.location.href = `{{ url('vehiculos/${id}/editar') }}`;
}

function abrirReporte(id, placa) {
    document.getElementById('reportePlaca').textContent = placa;
    document.getElementById('reporteModal').style.display = 'flex';

    fetch(`{{ url('api/reportes/filtro') }}?id_vehiculo=${id}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        let html = '';
        if (res.success && res.data) {
            window._reportDataDash = res.data;
            const totalIngresos = parseFloat(res.resumen?.total_ingresos || 0);
            const totalEgresos = parseFloat(res.resumen?.total_egresos || 0);
            const balance = totalIngresos - totalEgresos;
            const toneladas = res.data.filter(i => i.tipo_registro === 'INGRESO').reduce((s, i) => s + parseFloat(i.toneladas || 0), 0);

            html = `
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="p-3 text-center" style="background:#d4edda;border:3px solid #000;">
                            <div class="small fw-bold text-uppercase" style="color:#555;">Total Ingresos</div>
                            <div class="fs-5 fw-bold" style="color:#007400;">Bs. ${totalIngresos.toFixed(2)}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 text-center" style="background:#f8d7da;border:3px solid #000;">
                            <div class="small fw-bold text-uppercase" style="color:#555;">Total Egresos</div>
                            <div class="fs-5 fw-bold" style="color:#cc0000;">Bs. ${totalEgresos.toFixed(2)}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 text-center" style="background:#fff3cd;border:3px solid #000;">
                            <div class="small fw-bold text-uppercase" style="color:#555;">Balance Neto</div>
                            <div class="fs-5 fw-bold" style="color:${balance >= 0 ? '#007400' : '#cc0000'};">Bs. ${balance.toFixed(2)}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 text-center" style="background:#e2e3e5;border:3px solid #000;">
                            <div class="small fw-bold text-uppercase" style="color:#555;">Toneladas</div>
                            <div class="fs-5 fw-bold">${toneladas.toFixed(2)} tn</div>
                        </div>
                    </div>
                </div>
                <div style="border:3px solid #000;max-height:55vh;overflow-y:auto;">
                    <table class="table mb-0" id="reporteTableDash">
                        <thead class="sticky-top" style="background:#000;color:#ffc107;">
                            <tr>
                                <th style="padding:10px 12px;border:1px solid #333;">FECHA</th>
                                <th style="padding:10px 12px;border:1px solid #333;">TIPO</th>
                                <th style="padding:10px 12px;border:1px solid #333;">RECORRIDO</th>
                                <th style="padding:10px 12px;border:1px solid #333;text-align:right;">MONTO</th>
                                <th style="padding:10px 12px;border:1px solid #333;text-align:center;width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${res.data.map((item, i) => {
                                const recorrido = item.tipo_registro === 'INGRESO'
                                    ? ((item.origen || '') + (item.destino ? ' → ' + item.destino : ''))
                                    : (item.concepto || '—');
                                const monto = item.tipo_registro === 'INGRESO'
                                    ? parseFloat(item.ingreso || 0).toFixed(2)
                                    : parseFloat(item.egreso || 0).toFixed(2);
                                const bg = item.tipo_registro === 'INGRESO' ? '#e2ffd6' : '#ffdcd6';
                                const color = item.tipo_registro === 'INGRESO' ? '#007400' : '#cc0000';
                                const detalle = item.tipo_registro === 'INGRESO'
                                    ? '<span class="fw-bold">CLIENTE:</span> ' + (item.cliente_nombre || '—') + ' | <span class="fw-bold">COND:</span> ' + (item.conductor_asignado || '—') + ' | <span class="fw-bold">TN:</span> ' + (item.toneladas || '0') + ' | <span class="fw-bold">PAGO:</span> ' + (item.tipo_pago || '—')
                                    : '<span class="fw-bold">TIPO:</span> ' + (item.tipo_gasto || '—') + ' | <span class="fw-bold">PROV:</span> ' + (item.proveedor || '—') + ' | <span class="fw-bold">KM:</span> ' + (item.kilometraje || '—');
                                return `
                                <tr class="dash-tr-${i}" style="cursor:pointer;border-left:4px solid ${color};" onclick="toggleDetalleDash(${i})">
                                    <td style="padding:8px 12px;border:1px solid #ddd;white-space:nowrap;font-weight:600;">${item.fecha ? item.fecha.split(' ')[0] : '—'}</td>
                                    <td style="padding:8px 12px;border:1px solid #ddd;">
                                        <span class="badge fw-bold px-3 py-1" style="border:2px solid #000;background:${bg};color:#000;">${item.tipo_registro === 'INGRESO' ? '↑ INGRESO' : '↓ EGRESO'}</span>
                                    </td>
                                    <td style="padding:8px 12px;border:1px solid #ddd;font-weight:600;">${recorrido || '—'}</td>
                                    <td style="padding:8px 12px;border:1px solid #ddd;text-align:right;font-weight:700;color:${color};">Bs. ${monto}</td>
                                    <td style="padding:8px 12px;border:1px solid #ddd;text-align:center;font-weight:700;font-size:0.85rem;">▼</td>
                                </tr>
                                <tr id="dash-detalle-${i}" style="display:none;">
                                    <td colspan="5" style="padding:0;border:3px solid #000;border-top:none;background:#f9f9f9;">
                                        <div style="padding:12px 16px;">
                                            <div class="mb-1"><span class="fw-bold">N° DOC:</span> ${item.nro_documento || '—'}</div>
                                            <div class="mb-1">${detalle}</div>
                                            ${item.observaciones ? '<div class="mb-0"><span class="fw-bold">OBS:</span> ' + item.observaciones + '</div>' : ''}
                                        </div>
                                    </td>
                                </tr>`;
                            }).join('')}
                            ${res.data.length === 0 ? '<tr><td colspan="5" class="text-center py-5 fw-bold opacity-50">NO SE REGISTRARON MOVIMIENTOS</td></tr>' : ''}
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-end fw-bold" style="font-size:0.9rem;">
                    TOTAL INGRESOS: <span style="color:#007400;">Bs. ${totalIngresos.toFixed(2)}</span>
                    &nbsp;|&nbsp;
                    TOTAL EGRESOS: <span style="color:#cc0000;">Bs. ${totalEgresos.toFixed(2)}</span>
                    &nbsp;|&nbsp;
                    BALANCE: <span style="color:${balance >= 0 ? '#007400' : '#cc0000'};">Bs. ${balance.toFixed(2)}</span>
                </div>`;
        } else {
            html = '<div class="text-center py-5"><h4 class="fw-bold">SIN DATOS DISPONIBLES</h4></div>';
        }
        document.getElementById('reporteContent').innerHTML = html;
    })
    .catch(() => {
        document.getElementById('reporteContent').innerHTML = '<div class="text-center py-5"><h4 class="fw-bold text-danger">ERROR AL CARGAR REPORTE</h4></div>';
    });
}

function cerrarReporte() {
    document.getElementById('reporteModal').style.display = 'none';
}

function toggleDetalleDash(index) {
    const row = document.getElementById('dash-detalle-' + index);
    if (!row) return;
    const isHidden = row.style.display === 'none';
    row.style.display = isHidden ? 'table-row' : 'none';
    const tr = document.querySelector('.dash-tr-' + index);
    if (tr) tr.style.background = isHidden ? '#fffde7' : '';
}

function formatCurrency(val) {
    return 'Bs. ' + parseFloat(val || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
</script>
@endpush