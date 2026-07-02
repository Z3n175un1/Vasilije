@extends('layouts.master')

@section('title', $gasto ? 'Editar Gasto - VASILIJE' : 'Nuevo Gasto - VASILIJE')

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
                            @foreach(['Combustible', 'Mantenimiento', 'Peaje', 'Sueldo', 'Viático', 'Seguro', 'Lubricante', 'Llantas', 'Otro'] as $tipo)
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
                <div class="col-md-12">
                    <div class="d-flex align-items-center gap-3 p-3" style="background:#f0f0f0;border:3px solid #000;">
                        <span class="badge bg-black text-warning px-3 py-2 fw-bold fs-6 font-monospace">{{ $gasto->nro_documento ?? 'NUEVO' }}</span>
                        <span class="fw-bold small text-uppercase">{{ $gasto ? 'EDITANDO' : 'NUEVO REGISTRO' }}</span>
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
                        <label>GALONES</label>
                        <input type="number" step="0.01" name="galones" id="galones" value="{{ old('galones', $gasto->combustible->galones ?? '') }}" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>PRECIO/GALÓN (Bs)</label>
                        <input type="number" step="0.01" name="precio_por_galon" id="precioGalon" value="{{ old('precio_por_galon', $gasto->combustible->precio_por_galon ?? '') }}" min="0" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>KILOMETRAJE</label>
                        <input type="number" name="kilometraje" value="{{ old('kilometraje', $gasto->kilometraje ?? '') }}" min="0" placeholder="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>PROVEEDOR</label>
                        <select name="proveedor" id="proveedorSelect" class="form-control" style="border-radius:0;border:3px solid #000;padding:10px;">
                            <option value="">SELECCIONE PROVEEDOR...</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->nombre_proveedor }}" data-tipo="{{ $p->tipo_proveedor }}" {{ old('proveedor', $gasto->proveedor ?? '') == $p->nombre_proveedor ? 'selected' : '' }}>
                                    {{ $p->nombre_proveedor }}
                                </option>
                            @endforeach
                            <option value="OTRO">--- OTRO (ESCRIBIR MANUAL) ---</option>
                        </select>
                        <input type="text" id="proveedorOtro" name="proveedor_otro" value="" placeholder="NOMBRE DEL PROVEEDOR" style="display:none;border-radius:0;border:3px solid #000;padding:10px;width:100%;margin-top:6px;">
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

function filtrarProveedores() {
    const tipo = document.getElementById('tipoGasto').value;
    const select = document.getElementById('proveedorSelect');
    const currentVal = select.value;

    select.innerHTML = '<option value="">SELECCIONE PROVEEDOR...</option>';

    const tiposPermitidos = tipoMap[tipo] || ['GENERAL', null];
    const permitidos = Array.isArray(tiposPermitidos) ? tiposPermitidos : [tiposPermitidos];

    proveedores.forEach(p => {
        if (permitidos.includes(p.tipo_proveedor) || permitidos.includes(null)) {
            const opt = document.createElement('option');
            opt.value = p.nombre_proveedor;
            opt.textContent = p.nombre_proveedor + (p.rubro ? ' (' + p.rubro + ')' : '');
            opt.dataset.tipo = p.tipo_proveedor || '';
            select.appendChild(opt);
        }
    });

    select.innerHTML += '<option value="OTRO">--- OTRO (ESCRIBIR MANUAL) ---</option>';

    if ([...select.options].some(o => o.value === currentVal)) {
        select.value = currentVal;
    }
}

document.getElementById('proveedorSelect').addEventListener('change', function() {
    const otroInput = document.getElementById('proveedorOtro');
    if (this.value === 'OTRO') {
        otroInput.style.display = 'block';
        otroInput.name = 'proveedor';
        this.name = '';
    } else {
        otroInput.style.display = 'none';
        otroInput.name = 'proveedor_otro';
        this.name = 'proveedor';
    }
});

// On load, ensure selected proveedor shows correctly if it's from an existing gasto
document.addEventListener('DOMContentLoaded', function() {
    const selVal = document.getElementById('proveedorSelect').value;
    if (selVal && selVal !== 'OTRO') {
        // Check if it actually exists in the rendered options
        const exists = [...document.getElementById('proveedorSelect').options].some(o => o.value === selVal);
        if (!exists) {
            // Add it as a custom option
            const opt = document.createElement('option');
            opt.value = selVal;
            opt.textContent = selVal + ' (personalizado)';
            document.getElementById('proveedorSelect').appendChild(opt);
            document.getElementById('proveedorSelect').value = selVal;
        }
    }
    filtrarProveedores();
    toggleCombustible();
});
</script>
@endpush
