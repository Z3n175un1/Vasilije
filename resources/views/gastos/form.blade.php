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
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>TIPO GASTO <span class="text-danger">*</span></label>
                        <select name="tipo_gasto" id="tipoGasto" required onchange="toggleCombustible()">
                            @foreach(['Combustible', 'Mantenimiento', 'Peaje', 'Seguro', 'Lubricante', 'Llantas', 'Otro'] as $tipo)
                                <option value="{{ $tipo }}" {{ old('tipo_gasto', $gasto->tipo_gasto ?? '') == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>FECHA <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_gasto" value="{{ old('fecha_gasto', $gasto->fecha_gasto ?? date('Y-m-d')) }}" required>
                    </div>
                </div>
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
                        <input type="number" step="0.01" name="monto" value="{{ old('monto', $gasto->monto ?? '') }}" required min="0" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div id="combustibleSection" class="row g-4 mb-4" style="display:{{ old('tipo_gasto', $gasto->tipo_gasto ?? '') === 'Combustible' ? 'flex' : 'none' }};">
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
                <div class="col-md-4" id="campoMetodoPago">
                    <div class="form-group mb-0">
                        <label>MÉTODO DE PAGO</label>
                        <select name="metodo_pago" id="metodoPago" class="form-control" style="border-radius:0;border:3px solid #000;padding:10px;" onchange="toggleMetodoPago()">
                            <option value="BANCO" {{ old('metodo_pago', $gasto->metodo_pago ?? 'BANCO') == 'BANCO' ? 'selected' : '' }}>BANCO</option>
                            <option value="CAJA_CHICA" {{ old('metodo_pago', $gasto->metodo_pago ?? '') == 'CAJA_CHICA' ? 'selected' : '' }}>CAJA CHICA</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4" id="campoBanco">
                    <div class="form-group mb-0">
                        <label>CTA. BANCO</label>
                        <select name="id_banco" id="idBanco" class="form-control" style="border-radius:0;border:3px solid #000;padding:10px;">
                            <option value="">SELECCIONE BANCO...</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id_banco }}" {{ old('id_banco', $gasto->id_banco ?? '') == $b->id_banco ? 'selected' : '' }}>
                                    {{ $b->nombre_banco }} - {{ $b->numero_cuenta }} ({{ $b->moneda ?? 'BOB' }})
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
                        <select name="id_proveedor" id="proveedorSelect" class="form-control" style="border-radius:0;border:3px solid #000;padding:10px;">
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

// tipo_gasto → tipo_proveedor mapping
const tipoMap = {
    'Combustible': 'COMBUSTIBLE',
    'Mantenimiento': ['TALLER', 'MECANICO', 'REPUESTOS', 'FILTROS'],
    'Lubricante': 'ACEITES',
    'Llantas': 'LLANTAS',
    'Seguro': 'SEGURO',
    'Peaje': 'PEAJE',
};

function toggleCombustible() {
    const tipo = document.getElementById('tipoGasto').value;
    document.getElementById('combustibleSection').style.display = tipo === 'Combustible' ? 'flex' : 'none';
    filtrarProveedores();
}

function toggleCondicionPago() {
    const cond = document.getElementById('condicionPago').value;
    const esCredito = cond === 'CREDITO';
    document.getElementById('campoMetodoPago').style.display = esCredito ? 'none' : 'block';
    document.getElementById('creditoSection').style.display = esCredito ? 'flex' : 'none';
    toggleMetodoPago();
}

function toggleMetodoPago() {
    const metodo = document.getElementById('metodoPago').value;
    document.getElementById('campoBanco').style.display = metodo === 'BANCO' ? 'block' : 'none';
}

function calcMontoCombustible() {
    const litros = parseFloat(document.getElementById('litros').value) || 0;
    const precio = parseFloat(document.getElementById('precioLitro').value) || 0;
    if (litros > 0 && precio > 0) {
        const montoInput = document.querySelector('input[name="monto"]');
        if (montoInput) montoInput.value = (litros * precio).toFixed(2);
    }
}

document.addEventListener('input', function(e) {
    if (e.target.id === 'litros' || e.target.id === 'precioLitro') {
        if (document.getElementById('tipoGasto').value === 'Combustible') {
            calcMontoCombustible();
        }
    }
});

function filtrarProveedores() {
    const tipo = document.getElementById('tipoGasto').value;
    const select = document.getElementById('proveedorSelect');
    const currentVal = select.value;

    select.innerHTML = '<option value="">SELECCIONE PROVEEDOR...</option>';

    const tiposPermitidos = tipoMap[tipo] || ['GENERAL', null];
    const permitidos = Array.isArray(tiposPermitidos) ? tiposPermitidos : [tiposPermitidos];

    const filtrados = proveedores.filter(p =>
        permitidos.includes(p.tipo_proveedor) || permitidos.includes(null)
    );

    // If no matches, fallback to ALL proveedores
    const list = filtrados.length > 0 ? filtrados : proveedores;

    list.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id_proveedor;
        opt.textContent = p.nombre_proveedor;
        opt.dataset.tipo = p.tipo_proveedor || '';
        select.appendChild(opt);
    });

    // Restaurar la selección previa (id_proveedor) si sigue disponible
    if ([...select.options].some(o => o.value === currentVal)) {
        select.value = currentVal;
    }
}

document.getElementById('proveedorSelect').addEventListener('change', function() {
    // El select siempre envía id_proveedor
    this.name = 'id_proveedor';
});

document.addEventListener('DOMContentLoaded', function() {
    filtrarProveedores();
    toggleCombustible();
    toggleCondicionPago();
});
</script>
@endpush
