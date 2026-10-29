@extends('layouts.master')

@section('title', $gasto ? 'Editar Gasto General' : 'Registrar Gasto General')

@section('content')
<div class="main-container w-full">

    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">
                {{ $gasto ? 'EDITAR' : 'REGISTRAR' }} GASTO GENERAL
            </h1>
            <p class="font-bold small text-black uppercase">Servicios básicos · Impuestos · Alquiler · Caja chica</p>
        </div>
        <a href="{{ route('gastos-generales.index') }}"
           class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    @if($errors->any())
        <div class="bento-card mb-4" style="border:4px solid var(--danger);background:var(--soft-danger-bg);">
            <div class="fw-bold text-danger text-uppercase mb-2">
                <i class="fas fa-exclamation-triangle me-2"></i>Revise los siguientes errores
            </div>
            <ul class="mb-0 fw-bold" style="list-style:none;padding-left:0;">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bento-card" style="border:6px solid var(--line);">
        <form method="POST"
              action="{{ $gasto ? route('gastos-generales.update', $gasto->id_gasto_general) : route('gastos-generales.store') }}"
              class="form-bento">
            @csrf
            @if($gasto) @method('PUT') @endif

            <div class="row g-4 mb-4">
                {{-- GRUPO DE GASTOS: la categoria se deriva del grupo --}}
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>GRUPO GASTO <span class="text-danger">*</span></label>
                        <select name="id_grupo_gasto" id="idGrupoGasto" required
                                onchange="aplicarGrupo()">
                            <option value="">SELECCIONE...</option>
                            @foreach($gruposGastos as $grupo)
                                <option value="{{ $grupo->id_grupo_gasto }}"
                                        data-tipo="{{ $grupo->tipo_gasto ?? '' }}"
                                        {{ old('id_grupo_gasto', $gasto->id_grupo_gasto ?? $gasto->id_grupo_gasto ?? '') == $grupo->id_grupo_gasto ? 'selected' : '' }}>
                                    {{ $grupo->nombre }} @if($grupo->tipo_gasto)[{{ $grupo->tipo_gasto }}]@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>FECHA <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_gasto"
                               value="{{ $gasto->fecha_gasto ?? old('fecha_gasto', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>CONDICIÓN PAGO <span class="text-danger">*</span></label>
                        <select name="condicion_pago" id="condicionPago" required onchange="toggleCamposPago()">
                            <option value="CONTADO"
                                @selected(($gasto->condicion_pago ?? old('condicion_pago')) === 'CONTADO')>CONTADO</option>
                            <option value="CREDITO"
                                @selected(($gasto->condicion_pago ?? old('condicion_pago')) === 'CREDITO')>CRÉDITO</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="avisoTipo" class="mb-4 p-2 fw-bold text-uppercase"
                 style="display:none; background:var(--primary-soft); border:3px solid var(--primary);">
                Tipo de gasto: <span id="avisoTipoTexto"></span>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="form-group mb-0">
                        <label>CONCEPTO <span class="text-danger">*</span></label>
                        <input type="text" name="concepto"
                               value="{{ $gasto->concepto ?? old('concepto') }}"
                               required maxlength="255" placeholder="DESCRIPCIÓN DEL GASTO">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>MONTO (Bs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monto" id="montoInput"
                               value="{{ $gasto->monto ?? old('monto') }}" required placeholder="0.00">
                        <small class="d-block mt-2 text-black-50">
                            Negativo = <strong>devolución</strong>. No puede ser cero.
                        </small>
                    </div>
                </div>
            </div>

            {{-- Aviso de devolución --}}
            <div id="avisoDevolucion" class="mb-4 p-3"
                 style="display:none; background:var(--sun-soft); border:4px solid var(--danger);">
                <div class="fw-bold text-uppercase" style="color:var(--soft-danger);">
                    <i class="fas fa-undo me-2"></i> DEVOLUCIÓN DETECTADA
                </div>
                <div class="small mt-1" style="color:var(--soft-danger);">
                    El monto es negativo. Se registrará como devolución y no se descontará del total.
                </div>
            </div>

            {{-- Campos de pago --}}
            <div class="row g-4 mb-4" id="camposContado">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label id="labelBanco">BANCO</label>
                        <select name="id_banco" id="idBanco">
                            <option value="">NINGUNO</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id_banco }}"
                                    @selected(($gasto->id_banco ?? old('id_banco')) == $b->id_banco)>
                                    {{ $b->nombre_banco }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label id="labelProveedor">PROVEEDOR</label>
                        <select name="id_proveedor" id="idProveedor">
                            <option value="">NINGUNO</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id_proveedor }}"
                                    @selected(($gasto->id_proveedor ?? old('id_proveedor')) == $p->id_proveedor)>
                                    {{ $p->nombre_proveedor }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>N° COMPROBANTE</label>
                        <input type="text" name="nro_comprobante" maxlength="50"
                               value="{{ $gasto->nro_comprobante ?? old('nro_comprobante') }}"
                               placeholder="N° FACTURA/RECIBO">
                    </div>
                </div>
            </div>

            <div class="form-group mb-4" id="campoFechaLimite" style="display:none;">
                <label>FECHA POSIBLE PAGO</label>
                <input type="date" name="fecha_limite_pago"
                       value="{{ $gasto->fecha_limite_pago ?? old('fecha_limite_pago') }}">
            </div>

            <div class="form-group mb-4">
                <label>OBSERVACIONES</label>
                <textarea name="observaciones" rows="3" placeholder="OBSERVACIONES...">{{ $gasto->observaciones ?? old('observaciones') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('gastos-generales.index') }}"
                   class="btn-bento btn-bento-outline font-bold text-decoration-none"
                   style="border-width:4px!important;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $gasto ? 'ACTUALIZAR' : 'REGISTRAR' }} GASTO
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function grupoActual() {
    const sel = document.getElementById('idGrupoGasto');
    if (!sel || !sel.value) return null;
    return sel.options[sel.selectedIndex];
}

function aplicarGrupo() {
    const opcion = grupoActual();
    const aviso = document.getElementById('avisoTipo');
    const texto = document.getElementById('avisoTipoTexto');

    if (opcion && opcion.value) {
        aviso.style.display = 'block';
        texto.textContent = opcion.textContent.trim().replace(/\[.*\]/, '').trim();
    } else {
        aviso.style.display = 'none';
    }
}

/**
 * CONTADO se paga desde una cuenta; CREDITO genera deuda con un proveedor.
 * Se alterna la obligatoriedad en cliente; el servidor la revalida.
 */
function toggleCamposPago() {
    const esCredito = document.getElementById('condicionPago').value === 'CREDITO';

    const banco = document.getElementById('idBanco');
    const proveedor = document.getElementById('idProveedor');

    document.getElementById('labelBanco').textContent = esCredito ? 'BANCO (opcional)' : 'BANCO';
    document.getElementById('labelProveedor').textContent = esCredito ? 'PROVEEDOR *' : 'PROVEEDOR (opcional)';
    document.getElementById('campoFechaLimite').style.display = esCredito ? 'block' : 'none';

    banco.required = !esCredito;
    proveedor.required = esCredito;
}

function revisarDevolucion() {
    const monto = parseFloat(document.getElementById('montoInput').value);
    document.getElementById('avisoDevolucion').style.display =
        (!Number.isNaN(monto) && monto < 0) ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('montoInput').addEventListener('input', revisarDevolucion);

    aplicarGrupo();
    toggleCamposPago();
    revisarDevolucion();

    const form = document.querySelector('form.form-bento');

    if (form) {
        form.addEventListener('submit', function (e) {
            const monto = parseFloat(document.getElementById('montoInput').value);

            if (Number.isNaN(monto) || monto >= 0) {
                return;
            }

            e.preventDefault();

            const concepto = form.querySelector('input[name="concepto"]').value || '(sin concepto)';

            Swal.fire({
                title: '¿ESTÁ SEGURO DE REGISTRAR ESTA DEVOLUCIÓN?',
                html: `
                    <div class="text-start" style="font-size:.95rem;">
                        <p class="mb-2">
                            El monto es <strong style="color:var(--danger);">Bs. ${bs(monto)}</strong>.
                        </p>
                        <p class="mb-1"><strong>Concepto:</strong> ${esc(concepto)}</p>
                        <p class="mb-0 small">
                            Se registrará como devolución y no se descontará del total de gastos generales.
                        </p>
                    </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d6336c',
                cancelButtonColor: 'var(--muted)',
                confirmButtonText: 'SÍ, REGISTRAR DEVOLUCIÓN',
                cancelButtonText: 'CANCELAR',
                reverseButtons: true
            }).then((resultado) => {
                if (!resultado.isConfirmed) {
                    return;
                }

                Swal.fire({
                    title: 'REGISTRANDO DEVOLUCIÓN...',
                    html: 'Enviando el registro al servidor',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                // `form.submit()` y NO `requestSubmit()`.
                //
                // `requestSubmit()` vuelve a disparar el evento submit, asi que
                // entraria otra vez a este mismo manejador, abriria la
                // confirmacion de nuevo y el formulario se quedaria en bucle
                // pidiendo confirmacion.
                //
                // `submit()` envia el formulario de forma nativa, sin evento.
                // Es seguro porque este manejador solo llega aqui despues de
                // que el navegador ya valido los campos requeridos: el evento
                // submit no se dispara antes.
                form.submit();
            });
        });
    }
});
</script>
@endpush