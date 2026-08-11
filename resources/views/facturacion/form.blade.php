@extends('layouts.master')

@section('title', $ingreso ? 'Editar Flete' : 'Nuevo Flete')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $ingreso ? 'EDITAR' : 'NUEVO' }} FLETE</h1>
            <p class="font-bold small text-black uppercase">Registro de Ingresos y Facturación</p>
        </div>
        <a href="{{ route('facturacion.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $ingreso ? route('facturacion.update', $ingreso->id_ingreso) : route('facturacion.store') }}" class="form-bento">
            @csrf
            @if($ingreso) @method('PUT') @endif

            <div class="d-flex align-items-center gap-3 p-3 mb-4" style="background:#f0f0f0;border:3px solid #000;">
                <span class="badge bg-black text-warning px-3 py-2 fw-bold fs-6 font-monospace">{{ $ingreso->nro_documento ?? 'NUEVO' }}</span>
                <span class="fw-bold small text-uppercase">{{ $ingreso ? 'EDITANDO FLETE' : 'NUEVO REGISTRO' }}</span>
            </div>

            <!-- ROW 1: FECHA | UNIDAD | CONDUCTOR | NRO. DOCUMENTO -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>FECHA <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', $ingreso->fecha_ingreso ?? date('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>UNIDAD <span class="text-danger">*</span></label>
                        <select name="id_vehiculo" required>
                            <option value="">SELECCIONE...</option>
                            @foreach($vehiculos as $v)
                                <option value="{{ $v->id_vehiculo }}" {{ old('id_vehiculo', $ingreso->id_vehiculo ?? '') == $v->id_vehiculo ? 'selected' : '' }}>{{ $v->placa_vehiculo }} - {{ $v->marca }} {{ $v->modelo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>CONDUCTOR</label>
                        <select name="id_personal">
                            <option value="">SELECCIONE...</option>
                            @foreach($personal as $p)
                                <option value="{{ $p->id_personal }}" {{ old('id_personal', $ingreso->id_personal ?? '') == $p->id_personal ? 'selected' : '' }}>{{ $p->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>NRO. DOCUMENTO</label>
                        <input type="text" name="nro_documento" value="{{ old('nro_documento', $ingreso->nro_documento ?? '') }}" placeholder="Automático">
                    </div>
                </div>
            </div>

            <!-- ROW 2: RUTA -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="form-group mb-0">
                        <label>RUTA</label>
                        <select id="form_id_tramo" onchange="seleccionarRutaForm(this)">
                            <option value="">SELECCIONE RUTA...</option>
                            @foreach($tramos as $t)
                                <option value="{{ $t->id_tramo }}" data-origen="{{ $t->origen }}" data-destino="{{ $t->destino }}" data-km="{{ $t->kilometros }}" data-precio-ton="{{ $t->precio_dolar_tonelada }}">{{ $t->origen }} → {{ $t->destino }} (Bs. {{ $t->precio_total }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- ROW 3: ORIGEN | DESTINO -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>ORIGEN</label>
                        <input type="text" name="origen" id="form_origen" value="{{ old('origen', $ingreso->origen ?? '') }}" placeholder="Ciudad de origen" readonly style="background:#f0f0f0!important;cursor:not-allowed;">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>DESTINO</label>
                        <input type="text" name="destino" id="form_destino" value="{{ old('destino', $ingreso->destino ?? '') }}" placeholder="Ciudad de destino" readonly style="background:#f0f0f0!important;cursor:not-allowed;">
                    </div>
                </div>
            </div>

            <!-- ROW 4: KILOMETROS | BS X TON -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>KILÓMETROS</label>
                        <input type="number" step="0.01" name="kilometraje_conducido" id="form_kilometraje" value="{{ old('kilometraje_conducido', $ingreso->kilometraje_conducido ?? '0') }}" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>BS X TON</label>
                        <input type="number" step="0.01" name="precio_tonelada_bs" id="form_precio_ton" value="{{ old('precio_tonelada_bs', '') }}" min="0" placeholder="Bs por tonelada">
                    </div>
                </div>
            </div>

            <!-- ROW 5: TONELADAS | MONTO -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>TONELADAS</label>
                        <input type="number" step="0.01" name="toneladas" id="form_toneladas" value="{{ old('toneladas', $ingreso->toneladas ?? '0') }}" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>MONTO (Bs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monto" id="form_monto" value="{{ old('monto', $ingreso->monto ?? '0') }}" required min="0" placeholder="0.00" readonly style="background:#f0f0f0!important;cursor:not-allowed;">
                    </div>
                </div>
            </div>

            <!-- ROW 6: CLIENTE -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>CLIENTE / PROVEEDOR</label>
                        <input type="text" name="cliente_nombre" value="{{ old('cliente_nombre', $ingreso->cliente_nombre ?? 'INDUSTRIAS OLEAGINOSAS S.A.') }}" placeholder="INDUSTRIAS OLEAGINOSAS S.A.">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>TELÉFONO</label>
                        <input type="text" name="cliente_telefono" value="{{ old('cliente_telefono', $ingreso->cliente_telefono ?? '') }}" placeholder="Teléfono">
                    </div>
                </div>
            </div>

            <!-- ROW 7: CONCEPTO -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="form-group mb-0">
                        <label>CONCEPTO <span class="text-danger">*</span></label>
                        <input type="text" name="concepto" value="{{ old('concepto', $ingreso->concepto ?? 'TRANSPORTE DE SOYA') }}" required placeholder="TRANSPORTE DE SOYA">
                    </div>
                </div>
            </div>

            <!-- ROW 8: TIPO PAGO | FECHA VENCIMIENTO -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>TIPO DE PAGO</label>
                        <select name="tipo_pago">
                            @foreach(['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE', 'OTRO'] as $tp)
                                <option value="{{ $tp }}" {{ old('tipo_pago', $ingreso->tipo_pago ?? '') == $tp ? 'selected' : '' }}>{{ $tp }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>FECHA VENCIMIENTO</label>
                        <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', $ingreso->fecha_vencimiento ?? '') }}">
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>OBSERVACIONES</label>
                <textarea name="observaciones" rows="3" placeholder="Observaciones adicionales...">{{ old('observaciones', $ingreso->observaciones ?? '') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('facturacion.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $ingreso ? 'GUARDAR CAMBIOS' : 'REGISTRAR FLETE' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function seleccionarRutaForm(select) {
    const opt = select.options[select.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('form_origen').value = opt.dataset.origen || '';
        document.getElementById('form_destino').value = opt.dataset.destino || '';
        document.getElementById('form_kilometraje').value = opt.dataset.km || '0';
        const precioTon = parseFloat(opt.dataset.precioTon) || 0;
        document.getElementById('form_precio_ton').value = precioTon ? precioTon.toFixed(2) : '';
        recalcularMonto();
    }
}

function recalcularMonto() {
    const ton = parseFloat(document.getElementById('form_toneladas').value) || 0;
    const precioTon = parseFloat(document.getElementById('form_precio_ton').value) || 0;
    if (ton > 0 && precioTon > 0) {
        document.getElementById('form_monto').value = (ton * precioTon).toFixed(2);
    }
}

document.addEventListener('input', function(e) {
    if (e.target.id === 'form_toneladas' || e.target.id === 'form_precio_ton') {
        recalcularMonto();
    }
});
</script>
@endpush
