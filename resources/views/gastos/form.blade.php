@extends('layouts.master')

@section('title', $gasto ? 'Editar Gasto' : 'Nuevo Gasto')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $gasto ? 'EDITAR' : 'NUEVO' }} GASTO</h1>
            <p class="font-bold small text-black uppercase">Registro de Egresos Operativos</p>
</div>
        <a href="{{ route('dashboard.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $gasto ? route('gastos.update', $gasto->id_gasto) : route('gastos.store') }}" class="form-bento">
            @csrf
            @if($gasto) @method('PUT') @endif

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>VEHÍCULO <span class="text-danger">*</span></label>
                        <select name="id_vehiculo" required>
                            <option value="">SELECCIONE...</option>
                            @foreach($vehiculos as $v)
                                <option value="{{ $v->id_vehiculo }}" {{ old('id_vehiculo', $gasto->id_vehiculo ?? $id_vehiculo ?? '') == $v->id_vehiculo ? 'selected' : '' }}>
                                    {{ $v->placa_vehiculo }} - {{ $v->marca ?? '' }} {{ $v->modelo ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- CLASIFICADOR DE GASTO: solo los de alcance UNIDAD --}}
                <div class="col-md-5">
                    <div class="form-group mb-0">
                        <label>CLASIFICADOR DE GASTO <span class="text-danger">*</span></label>
                        <select name="id_clasificador" id="idClasificador" required
                                onchange="aplicarClasificador()">
                            <option value="">SELECCIONE...</option>
                            @foreach($clasificadores as $c)
                                <option value="{{ $c->id_clasificador }}"
                                        data-tipo="{{ $c->tipo_gasto }}"
                                        data-codigo="{{ $c->codigo }}"
                                        {{ old('id_clasificador', $gasto->id_clasificador ?? '') == $c->id_clasificador ? 'selected' : '' }}>
                                    {{ $c->codigo }} — {{ $c->descripcion }} [{{ $c->tipo_gasto }}]
                                </option>
                            @endforeach
                        </select>
                        <small class="d-block mt-2 text-black-50">
                            Solo se muestran los clasificadores que <strong>afectan a unidad</strong>.
                            El tipo de gasto se deriva del clasificador.
                        </small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>FECHA <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_gasto" value="{{ old('fecha_gasto', $gasto->fecha_gasto ?? date('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>

            {{-- Aviso del tipo derivado --}}
            <div id="avisoTipo" class="mb-4 p-2 fw-bold text-uppercase"
                 style="display:none; background:#eef2ff; border:3px solid #2f2c79;">
                Tipo de gasto: <span id="avisoTipoTexto"></span>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="form-group mb-0">
                        <label>CONCEPTO <span class="text-danger">*</span></label>
                        <input type="text" name="concepto" value="{{ old('concepto', $gasto->concepto ?? '') }}" required placeholder="DESCRIPCIÓN DEL GASTO">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>MONTO (Bs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monto" id="montoInput"
                               value="{{ old('monto', $gasto->monto ?? '') }}" required placeholder="0.00">
                        <small class="d-block mt-2 text-black-50">
                            Un monto <strong>negativo</strong> registra una <strong>devolución</strong>.
                        </small>
                    </div>
                </div>
            </div>

            {{-- Aviso de devolución --}}
            <div id="avisoDevolucion" class="mb-4 p-3"
                 style="display:none; background:#fff3cd; border:4px solid #dc3545;">
                <div class="fw-bold text-uppercase" style="color:#740000;">
                    <i class="fas fa-undo me-2"></i> DEVOLUCIÓN DETECTADA
                </div>
                <div class="small mt-1" style="color:#740000;">
                    El monto es negativo. Se registrará como devolución y quedará marcado como
                    <strong>Anulado</strong>, invirtiendo su efecto en los reportes.
                </div>
            </div>

            <div id="combustibleSection" class="row g-4 mb-4" style="display:none;">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>TIPO COMBUSTIBLE</label>
                        <select name="tipo_combustible">
                            <option value="Diesel" {{ old('tipo_combustible', $gasto->combustible->tipo_carburante ?? '') === 'Diesel' ? 'selected' : '' }}>Diesel</option>
                            <option value="Gasolina" {{ old('tipo_combustible', $gasto->combustible->tipo_carburante ?? '') === 'Gasolina' ? 'selected' : '' }}>Gasolina</option>
                            <option value="GNV" {{ old('tipo_combustible', $gasto->combustible->tipo_carburante ?? '') === 'GNV' ? 'selected' : '' }}>GNV</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>LITROS</label>
                        <input type="number" step="0.01" name="litros" id="litros" value="{{ old('litros', $gasto->combustible->galones ?? '') }}" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>PRECIO/LITRO (Bs)</label>
                        <input type="number" step="0.01" name="precio_por_litro" id="precioLitro" value="{{ old('precio_por_litro', $gasto->combustible->precio_por_galon ?? '') }}" min="0" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>CONDICIÓN DE PAGO <span class="text-danger">*</span></label>
                        <select name="condicion_pago" id="condicionPago" class="form-control" style="border-radius:0;border:3px solid #000;padding:10px;" onchange="toggleCondicionPago()">
                            <option value="CONTADO" {{ old('condicion_pago', $gasto->condicion_pago ?? 'CONTADO') == 'CONTADO' ? 'selected' : '' }}>CONTADO</option>
                            <option value="CREDITO" {{ old('condicion_pago', $gasto->condicion_pago ?? 'CONTADO') == 'CREDITO' ? 'selected' : '' }}>CRÉDITO</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4" id="campoBanco">
                    <div class="form-group mb-0">
                        <label>CTA. BANCO <span class="text-danger">*</span></label>
                        <select name="id_banco" id="idBanco" class="form-control" style="border-radius:0;border:3px solid #000;padding:10px;" required>
                            <option value="">SELECCIONE BANCO...</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id_banco }}" {{ old('id_banco', $gasto->id_banco ?? '') == $b->id_banco ? 'selected' : '' }}>
                                    {{ $b->nombre_banco }} - {{ $b->numero_cuenta }} ({{ $b->moneda ?? 'BOB' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4" id="campoProveedorContado">
                    <div class="form-group mb-0">
                        <label>PROVEEDOR</label>
                        <select name="id_proveedor" id="proveedorContadoSelect" class="form-control" style="border-radius:0;border:3px solid #000;padding:10px;">
                            <option value="">SELECCIONE PROVEEDOR...</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id_proveedor }}" data-tipo="{{ $p->tipo_proveedor }}" {{ old('id_proveedor', $gasto->id_proveedor ?? '') == $p->id_proveedor ? 'selected' : '' }}>
                                    {{ $p->nombre_proveedor }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- CRÉDITO: proveedor obligatorio + fecha posible pago -->
            <div id="creditoSection" class="row g-4 mb-4" style="display:none;">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>PROVEEDOR <span class="text-danger">*</span></label>
                        <select name="id_proveedor" id="proveedorCreditoSelect" class="form-control" style="border-radius:0;border:3px solid #000;padding:10px;">
                            <option value="">SELECCIONE PROVEEDOR...</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id_proveedor }}" data-tipo="{{ $p->tipo_proveedor }}" {{ old('id_proveedor', $gasto->id_proveedor ?? '') == $p->id_proveedor ? 'selected' : '' }}>
                                    {{ $p->nombre_proveedor }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6" id="campoFechaLimite">
                    <div class="form-group mb-0">
                        <label>FECHA POSIBLE PAGO</label>
                        <input type="date" name="fecha_limite_pago" id="fechaLimitePago" value="{{ old('fecha_limite_pago', $gasto->fecha_limite_pago ?? '') }}">
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>DESCRIPCIÓN</label>
                <textarea name="descripcion" rows="3" placeholder="DETALLE ADICIONAL...">{{ old('descripcion', $gasto->descripcion ?? '') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('dashboard.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $gasto ? 'GUARDAR CAMBIOS' : 'REGISTRAR GASTO' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const proveedores = @json($proveedores);

/**
 * tipo_gasto -> tipo_proveedor. El tipo ya no viene de un select propio:
 * se deriva del clasificador seleccionado.
 */
const tipoMap = {
    'Combustible': 'COMBUSTIBLE',
    'Mantenimiento': ['TALLER', 'MECANICO', 'REPUESTOS', 'FILTROS'],
    'Lubricante': 'ACEITES',
    'Llantas': 'LLANTAS',
    'Seguro': 'SEGURO',
    'Peaje': 'PEAJE',
};

function clasificadorActual() {
    const sel = document.getElementById('idClasificador');
    if (!sel || !sel.value) return null;
    return sel.options[sel.selectedIndex];
}

/**
 * Sincroniza la UI con el tipo del clasificador: muestra el bloque de
 * combustible y filtra los proveedores por rubro.
 */
function aplicarClasificador() {
    const opcion = clasificadorActual();
    const tipo = opcion ? opcion.dataset.tipo : '';

    document.getElementById('combustibleSection').style.display =
        (tipo === 'Combustible') ? 'flex' : 'none';

    const aviso = document.getElementById('avisoTipo');
    const texto = document.getElementById('avisoTipoTexto');
    if (tipo) {
        aviso.style.display = 'block';
        texto.textContent = opcion.dataset.codigo + ' / ' + tipo;
    } else {
        aviso.style.display = 'none';
    }

    filtrarProveedores(tipo);
}

function toggleCondicionPago() {
    const cond = document.getElementById('condicionPago').value;
    const esCredito = cond === 'CREDITO';
    const banco = document.getElementById('idBanco');
    document.getElementById('campoBanco').style.display = esCredito ? 'none' : 'block';
    document.getElementById('campoProveedorContado').style.display = esCredito ? 'none' : 'block';
    document.getElementById('creditoSection').style.display = esCredito ? 'flex' : 'none';
    banco.required = !esCredito;
    const selCredito = document.getElementById('proveedorCreditoSelect');
    if (selCredito) selCredito.required = esCredito;
}

function calcMontoCombustible() {
    const litros = parseFloat(document.getElementById('litros').value) || 0;
    const precio = parseFloat(document.getElementById('precioLitro').value) || 0;
    const montoInput = document.getElementById('montoInput');
    // No se sobreescribe si el monto es negativo: es una devolucion y el
    // operador probablemente lo puso a proposito.
    if (litros > 0 && precio > 0 && !(parseFloat(montoInput.value) < 0)) {
        montoInput.value = (litros * precio).toFixed(2);
        revisarDevolucion();
    }
}

document.addEventListener('input', function(e) {
    if (e.target.id === 'litros' || e.target.id === 'precioLitro') {
        const opcion = clasificadorActual();
        if (opcion && opcion.dataset.tipo === 'Combustible') {
            calcMontoCombustible();
        }
    }
    if (e.target.id === 'montoInput') {
        revisarDevolucion();
    }
});

function filtrarProveedores(tipo) {
    const tiposPermitidos = tipoMap[tipo] || ['GENERAL', null];
    const permitidos = Array.isArray(tiposPermitidos) ? tiposPermitidos : [tiposPermitidos];

    const filtrados = proveedores.filter(p =>
        permitidos.includes(p.tipo_proveedor) || permitidos.includes(null)
    );

    // Si el rubro no tiene coincidencias se ofrecen todos: es preferible un
    // proveedor de otra clase a quedarse sin opciones.
    const lista = filtrados.length > 0 ? filtrados : proveedores;

    ['proveedorContadoSelect', 'proveedorCreditoSelect'].forEach(selId => {
        const select = document.getElementById(selId);
        if (!select) return;
        const valorPrevio = select.value;

        select.innerHTML = '<option value="">SELECCIONE PROVEEDOR...</option>';

        lista.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id_proveedor;
            opt.textContent = p.nombre_proveedor;
            opt.dataset.tipo = p.tipo_proveedor || '';
            select.appendChild(opt);
        });

        if ([...select.options].some(o => o.value === valorPrevio)) {
            select.value = valorPrevio;
        }
    });
}

/**
 * Marca visualmente cuando el monto es negativo (devolucion).
 */
function revisarDevolucion() {
    const monto = parseFloat(document.getElementById('montoInput').value);
    document.getElementById('avisoDevolucion').style.display =
        (!Number.isNaN(monto) && monto < 0) ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('condicionPago')?.addEventListener('change', toggleCondicionPago);
    document.getElementById('idClasificador')?.addEventListener('change', aplicarClasificador);

    aplicarClasificador();
    toggleCondicionPago();
    revisarDevolucion();

    /**
     * REGLA DE DEVOLUCION
     * -------------------
     * Un monto negativo significa devolucion. Antes de enviar el formulario
     * se pide confirmacion explicita, porque el efecto es contrario al
     * habitual (suma en vez de restar al balance).
     */
    const form = document.querySelector('form.form-bento');

    if (form) {
        form.addEventListener('submit', function(e) {
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
                            El monto es <strong style="color:#dc3545;">Bs. ${bs(monto)}</strong>.
                        </p>
                        <p class="mb-1"><strong>Concepto:</strong> ${esc(concepto)}</p>
                        <p class="mb-0 small">
                            Se registrará como devolución y quedará marcado como <strong>Anulado</strong>.
                            Invertirá su efecto en los reportes y en el estado de cuenta.
                        </p>
                    </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
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

                // `submit()` y no `requestSubmit()`: ver la nota equivalente
                // en gastos-generales sobre por qué re-disparar el evento
                // submit entraría en bucle.
                form.submit();
            });
        });
    }
});
</script>
@endpush
