@extends('layouts.master')

@section('title', 'Registrar Viático - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">REGISTRAR VIÁTICO</h1>
            <p class="font-bold small text-black uppercase">Viáticos y Gastos de Viaje - {{ $personal->nombres }} {{ $personal->apellidos }}</p>
        </div>
        <a href="{{ route('personal.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ route('personal.gasto.store') }}" class="form-bento">
            @csrf
            <input type="hidden" name="id_personal" value="{{ $personal->id_personal }}">
            <input type="hidden" name="tipo_gasto" value="Viático">

            <div class="d-flex align-items-center gap-3 p-3 mb-4" style="background:#f0f0f0;border:3px solid #000;">
                <span class="badge bg-black text-warning px-3 py-2 fw-bold fs-6 font-monospace">VIÁTICO</span>
                <span class="fw-bold small text-uppercase">{{ $personal->nombres }} {{ $personal->apellidos }} ({{ $personal->ci ?? 'SIN CI' }})</span>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>MONTO (Bs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monto" value="{{ old('monto') }}" required min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>FECHA <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_gasto" value="{{ old('fecha_gasto', date('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>DESTINO</label>
                        <input type="text" name="destino_viatico" value="{{ old('destino_viatico') }}" placeholder="CIUDAD/LUGAR">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-12">
                    <div class="form-group mb-0">
                        <label>CONCEPTO <span class="text-danger">*</span></label>
                        <input type="text" name="concepto" value="{{ old('concepto', 'Viático ' . $personal->nombres . ' ' . $personal->apellidos) }}" required placeholder="DESCRIPCIÓN">
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>OBSERVACIONES</label>
                <textarea name="descripcion" rows="3" placeholder="OBSERVACIONES...">{{ old('descripcion') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('personal.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> REGISTRAR VIÁTICO
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
