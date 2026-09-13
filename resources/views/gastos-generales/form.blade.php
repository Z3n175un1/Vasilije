@extends('layouts.master')

@section('title', $gasto ? 'Editar Gasto General' : 'Nuevo Gasto General')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $gasto ? 'EDITAR' : 'REGISTRAR' }} GASTO GENERAL</h1>
            <p class="font-bold small text-black uppercase">Caja Chica, Servicios Básicos, Impuestos</p>
        </div>
        <a href="{{ route('gastos-generales.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $gasto ? url('gastos-generales/' . $gasto->id_gasto_general) : route('gastos-generales.store') }}" class="form-bento">
            @csrf
            @if($gasto)
                @method('PUT')
            @endif

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>CATEGORÍA <span class="text-danger">*</span></label>
                        <select name="categoria" class="form-select" required>
                            <option value="">SELECCIONAR...</option>
                            <option value="Caja Chiva" {{ ($gasto->categoria ?? old('categoria')) === 'Caja Chiva' ? 'selected' : '' }}>CAJA CHICA</option>
                            <option value="Servicios Básicos" {{ ($gasto->categoria ?? old('categoria')) === 'Servicios Básicos' ? 'selected' : '' }}>SERVICIOS BÁSICOS</option>
                            <option value="Impuestos" {{ ($gasto->categoria ?? old('categoria')) === 'Impuestos' ? 'selected' : '' }}>IMPUESTOS</option>
                            <option value="Telecomunicaciones" {{ ($gasto->categoria ?? old('categoria')) === 'Telecomunicaciones' ? 'selected' : '' }}>TELECOMUNICACIONES</option>
                            <option value="Alquiler" {{ ($gasto->categoria ?? old('categoria')) === 'Alquiler' ? 'selected' : '' }}>ALQUILER</option>
                            <option value="Seguros" {{ ($gasto->categoria ?? old('categoria')) === 'Seguros' ? 'selected' : '' }}>SEGUROS</option>
                            <option value="Varios" {{ ($gasto->categoria ?? old('categoria')) === 'Varios' ? 'selected' : '' }}>VARIOS</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>FECHA <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_gasto" value="{{ $gasto->fecha_gasto ?? old('fecha_gasto', date('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>MONTO (Bs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monto" value="{{ $gasto->monto ?? old('monto') }}" required min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>CONDICIÓN PAGO <span class="text-danger">*</span></label>
                        <select name="condicion_pago" id="condicionPago" class="form-select" required onchange="toggleCamposPago()">
                            <option value="CONTADO" {{ ($gasto->condicion_pago ?? old('condicion_pago')) === 'CONTADO' ? 'selected' : '' }}>CONTADO</option>
                            <option value="CREDITO" {{ ($gasto->condicion_pago ?? old('condicion_pago')) === 'CREDITO' ? 'selected' : '' }}>CRÉDITO</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-12">
                    <div class="form-group mb-0">
                        <label>CONCEPTO <span class="text-danger">*</span></label>
                        <input type="text" name="concepto" value="{{ $gasto->concepto ?? old('concepto') }}" required placeholder="DESCRIPCIÓN DEL GASTO" maxlength="255">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4" id="camposContado">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>BANCO</label>
                        <select name="id_banco" class="form-select">
                            <option value="">NINGUNO</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id_banco }}" {{ ($gasto->id_banco ?? old('id_banco')) == $b->id_banco ? 'selected' : '' }}>{{ $b->nombre_banco }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>PROVEEDOR</label>
                        <select name="id_proveedor" class="form-select">
                            <option value="">NINGUNO</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id_proveedor }}" {{ ($gasto->id_proveedor ?? old('id_proveedor')) == $p->id_proveedor ? 'selected' : '' }}>{{ $p->nombre_proveedor }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>N° COMPROBANTE</label>
                        <input type="text" name="nro_comprobante" value="{{ $gasto->nro_comprobante ?? old('nro_comprobante') }}" placeholder="N° FACTURA/RECIBO" maxlength="50">
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>OBSERVACIONES</label>
                <textarea name="observaciones" rows="3" placeholder="OBSERVACIONES...">{{ $gasto->observaciones ?? old('observaciones') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('gastos-generales.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $gasto ? 'ACTUALIZAR' : 'REGISTRAR' }} GASTO
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCamposPago() {
    const condicion = document.getElementById('condicionPago').value;
    const camposContado = document.getElementById('camposContado');
    // Siempre mostrar los campos, pero cambiar comportamiento si es necesario
}
</script>
@endsection
