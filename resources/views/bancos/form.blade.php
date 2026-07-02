@extends('layouts.master')

@section('title', $banco ? 'Editar Banco - VASILIJE' : 'Nuevo Banco - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $banco ? 'EDITAR' : 'NUEVO' }} BANCO</h1>
            <p class="font-bold small text-black uppercase">Control de Cuentas Bancarias</p>
        </div>
        <a href="{{ route('bancos.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $banco ? route('bancos.update', $banco->id_banco) : route('bancos.store') }}" class="form-bento">
            @csrf
            @if($banco) @method('PUT') @endif

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>NOMBRE DEL BANCO <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_banco" value="{{ old('nombre_banco', $banco->nombre_banco ?? '') }}" required placeholder="EJ. BANCO NACIONAL">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>N° CUENTA <span class="text-danger">*</span></label>
                        <input type="text" name="numero_cuenta" value="{{ old('numero_cuenta', $banco->numero_cuenta ?? '') }}" required placeholder="NÚMERO DE CUENTA">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>TITULAR <span class="text-danger">*</span></label>
                        <input type="text" name="titular" value="{{ old('titular', $banco->titular ?? '') }}" required placeholder="NOMBRE DEL TITULAR">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>TIPO CUENTA</label>
                        <select name="tipo_cuenta">
                            @foreach(['AHORROS', 'CORRIENTE', 'EMPRESARIAL'] as $tipo)
                                <option value="{{ $tipo }}" {{ old('tipo_cuenta', $banco->tipo_cuenta ?? '') == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>MONEDA</label>
                        <select name="moneda">
                            @foreach(['BOB', 'USD', 'EUR'] as $moneda)
                                <option value="{{ $moneda }}" {{ old('moneda', $banco->moneda ?? '') == $moneda ? 'selected' : '' }}>{{ $moneda }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>SALDO INICIAL (Bs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="saldo_inicial" value="{{ old('saldo_inicial', $banco->saldo_inicial ?? '0') }}" required min="0">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('bancos.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $banco ? 'GUARDAR CAMBIOS' : 'REGISTRAR BANCO' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
