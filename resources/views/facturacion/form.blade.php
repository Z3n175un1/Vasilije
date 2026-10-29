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

    <div class="bento-card" style="border:6px solid var(--line);">
        <form method="POST" action="{{ $ingreso ? route('facturacion.update', $ingreso->id_ingreso) : route('facturacion.store') }}" class="form-bento">
            @csrf
            @if($ingreso) @method('PUT') @endif

            <div class="d-flex align-items-center gap-3 p-3 mb-4" style="background:var(--primary-soft);border:3px solid var(--line-strong);">
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

            <!-- ROW 2: RUTA | TIPO DE CAMBIO DE TRABAJO -->
            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="form-group mb-0">
                        <label>RUTA</label>

                        {{-- El `name` es lo que hace que el tramo viaje al
                             servidor. Sin el, el select se ve y funciona pero
                             no envia nada. --}}
                        <select name="id_tramo" id="form_id_tramo" onchange="seleccionarRutaForm(this)">
                            <option value="">SELECCIONE RUTA...</option>

                            @foreach($tramos as $t)
                                <option value="{{ $t->id_tramo }}"
                                        data-origen="{{ $t->origen }}"
                                        data-destino="{{ $t->destino }}"
                                        data-km="{{ $t->kilometros }}"
                                        data-precio-ton="{{ $t->precio_dolar_tonelada }}"
                                        {{ (string) old('id_tramo', $ingreso->id_tramo ?? '') === (string) $t->id_tramo ? 'selected' : '' }}>
                                    {{ $t->origen }} → {{ $t->destino }}
                                </option>
                            @endforeach
                        </select>

                        {{-- `onchange` solo dispara cuando el valor cambia, asi
                             que volver a elegir la MISMA ruta no recarga nada.
                             Este botón fuerza la recarga. --}}
                        <button type="button"
                                class="btn btn-sm fw-bold mt-2"
                                style="background:var(--primary);color:#fff;border:3px solid var(--line-strong);padding:8px 14px;"
                                onclick="seleccionarRutaForm(document.getElementById('form_id_tramo'))">
                            <i class="fas fa-rotate-right me-1"></i> APLICAR RUTA
                        </button>

                        <small class="d-block mt-2 fw-bold" style="color:#666;">
                            Al elegir la ruta se completan kilómetros y la TARIFA (US$/TON).
                            El monto en US$ es toneladas &times; US$/TON; el monto en Bs sale al TC de trabajo.
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>TIPO DE CAMBIO DE TRABAJO (Bs/USD)</label>
                        <input type="number" step="0.01" name="tipo_cambio" id="form_tipo_cambio"
                               value="{{ old('tipo_cambio', $ingreso->tipo_cambio ?? $tipo_cambio_vigente) }}"
                               min="0" placeholder="6.96" readonly
                               style="background:var(--primary-soft)!important;cursor:not-allowed;">
                        <small class="d-block mt-2 fw-bold" style="color:#666;">
                            Automático por la FECHA del flete: 6,96 hasta el 30/09/2025;
                            después, el oficial BCB de esa fecha.
                        </small>
                    </div>
                </div>
            </div>

            <!-- ROW 3: KILOMETROS | TARIFA US$/TON | TARIFA Bs/TON -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>KILÓMETROS</label>
                        <input type="number" step="0.01" name="kilometraje_conducido" id="form_kilometraje" value="{{ old('kilometraje_conducido', $ingreso->kilometraje_conducido ?? '0') }}" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>TARIFA (US$/TON)</label>
                        <input type="number" step="0.01" name="precio_tonelada_usd" id="form_precio_ton" value="{{ old('precio_tonelada_usd', '') }}" min="0" placeholder="US$ por tonelada">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>TARIFA (Bs/TON)</label>
                        <input type="number" step="0.01" name="precio_tonelada_bs" id="form_precio_ton_bs"
                               value="{{ old('precio_tonelada_bs', '') }}" min="0" placeholder="0.00" readonly
                               style="background:var(--primary-soft)!important;cursor:not-allowed;">
                        <small class="d-block mt-2 fw-bold" style="color:#666;">
                            = US$/TON &times; TC de trabajo
                        </small>
                    </div>
                </div>
            </div>

            <!-- ROW 5: TONELADAS | MONTO US$ -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>TONELADAS</label>
                        <input type="number" step="0.01" name="toneladas" id="form_toneladas" value="{{ old('toneladas', $ingreso->toneladas ?? '0') }}" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>MONTO (US$) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monto_usd" id="form_monto_usd"
                               value="{{ old('monto_usd', $ingreso->monto_usd ?? '') }}"
                               required min="0" placeholder="0.00">
                        <small class="d-block mt-2 text-black-50">
                            Obligatorio: toneladas &times; US$/TON. Es lo que se factura al TC de trabajo.
                        </small>
                    </div>
                </div>
            </div>

            <!-- ROW 6: MONTO Bs -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>MONTO (Bs)</label>
                        <input type="number" step="0.01" name="monto" id="form_monto" value="{{ old('monto', $ingreso->monto ?? '0') }}" required min="0" placeholder="0.00" readonly style="background:var(--primary-soft)!important;cursor:not-allowed;">
                        <small class="d-block mt-1 fw-bold text-muted">= Monto US$ &times; TC de trabajo (automatico)</small>
                    </div>
                </div>
            </div>

            <!-- ROW 7: CLIENTE -->
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
/**
 * Aplica la ruta seleccionada a los campos que dependen de ella.
 *
 * Se invoca desde el `onchange` del select y desde el boton APLICAR RUTA,
 * porque `onchange` no dispara cuando se elige la MISMA opcion: si el
 * operador se equivoco de ruta y vuelve a marcar la misma, no habria forma
 * de recargar los datos sin tocar otro campo.
 */
function seleccionarRutaForm(select) {
    const opt = select.options[select.selectedIndex];
    const kilometros = document.getElementById('form_kilometraje');
    const precioTon = document.getElementById('form_precio_ton');
    const toneladas = document.getElementById('form_toneladas');

    // Volver a "SELECCIONE RUTA..." limpia los campos: si no, el operador
    // ve una ruta en el select y los datos de otra en los campos de abajo.
    if (!opt || !opt.value) {
        kilometros.value = '0';
        precioTon.value = '';

        recalcularMonto();

        return;
    }

    kilometros.value = opt.dataset.km || '0';

    const precio = parseFloat(opt.dataset.precioTon) || 0;
    precioTon.value = precio ? precio.toFixed(2) : '';

    // Las toneladas NO se tocan: son dato del flete, no de la ruta. Se
    // recalcula el monto para que el operador vea el efecto del nuevo
    // Bs/TON sobre las toneladas que ya habia escrito.
    recalcularMonto();

    if (toneladas) {
        toneladas.focus();
    }
}

/**
 * Calcula MONTO (US$) = TONELADAS x TARIFA (US$/TON) y, a partir de ese,
 * MONTO (Bs) = US$ x TC DE TRABAJO.
 *
 * Antes solo recalculaba si ambas eran mayores a cero. Con las toneladas en
 * blanco el monto se quedaba con el valor de la carga anterior, que es lo
 * que hacia que el Bs/TON "no se calculara" y el total quedara desfasado.
 */
function recalcularMonto() {
    const toneladas = parseFloat(document.getElementById('form_toneladas').value) || 0;
    const tarifaUsd = parseFloat(document.getElementById('form_precio_ton').value) || 0;
    const tc = parseFloat(document.getElementById('form_tipo_cambio').value) || 0;

    const montoUsd = document.getElementById('form_monto_usd');
    const montoBs = document.getElementById('form_monto');
    const precioBs = document.getElementById('form_precio_ton_bs');

    if (toneladas > 0 && tarifaUsd > 0) {
        montoUsd.value = (toneladas * tarifaUsd).toFixed(2);
    } else {
        // Sin datos suficientes el monto se pone en cero en vez de conservar
        // un valor que ya no corresponde a lo que hay en pantalla.
        montoUsd.value = '0.00';
    }

    montoBs.value = ((parseFloat(montoUsd.value) || 0) * tc).toFixed(2);

    // La tarifa en Bs/TON viaja al TC con el que se esta trabajando: si no
    // hay TC todavia (fecha sin registro), queda en cero, no en vacio.
    if (precioBs) {
        precioBs.value = (tarifaUsd * tc).toFixed(2);
    }
}

/**
 * El TC de trabajo es automatico por FECHA del flete: 6,96 hasta el
 * 30/09/2025 y despues el oficial BCB de esa fecha. Al cambiar el campo
 * FECHA se pide el valor al endpoint interno y se recalcula el BS.
 */
function actualizarTipoCambio() {
    const fecha = document.querySelector('input[name="fecha_ingreso"]')?.value;

    if (!fecha) {
        return;
    }

    fetch('{{ url("api/tipo-cambio-para-fecha") }}?fecha=' + fecha, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success || !res.data) return;
        const tc = parseFloat(res.data.valor) || 0;
        if (tc > 0) {
            document.getElementById('form_tipo_cambio').value = tc.toFixed(2);
            // Recalcula monto US$, monto Bs y la TARIFA Bs/TON al nuevo TC.
            recalcularMonto();
        }
    })
    .catch(() => { /* sin historico disponible se queda el valor actual */ });
}

document.addEventListener('input', function (e) {
    if (['form_toneladas', 'form_precio_ton'].includes(e.target.id)) {
        recalcularMonto();
    }
});

document.addEventListener('change', function (e) {
    if (e.target.name === 'fecha_ingreso') {
        actualizarTipoCambio();
    }
});

// Al editar un flete, la ruta ya viene seleccionada desde el servidor: hay
// que aplicar sus datos para que kilometros y TARIFA US$/TON no queden vacios.
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('form_id_tramo');

    if (!select || !select.value) {
        recalcularMonto();
        return;
    }

    seleccionarRutaForm(select);
});
</script>
@endpush
