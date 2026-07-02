@extends('layouts.master')

@section('title', $tramo ? 'Editar Ruta - VASILIJE' : 'Nueva Ruta - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $tramo ? 'EDITAR' : 'NUEVA' }} RUTA</h1>
            <p class="font-bold small text-black uppercase">Gestión de Tramos y Recorridos</p>
        </div>
        <a href="{{ route('tramos.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $tramo ? route('tramos.update', $tramo->id_tramo) : route('tramos.store') }}" class="form-bento">
            @csrf
            @if($tramo) @method('PUT') @endif

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>ORIGEN <span class="text-danger">*</span></label>
                        <input type="text" name="origen" value="{{ old('origen', $tramo->origen ?? '') }}" required placeholder="CIUDAD DE ORIGEN">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>DESTINO <span class="text-danger">*</span></label>
                        <input type="text" name="destino" value="{{ old('destino', $tramo->destino ?? '') }}" required placeholder="CIUDAD DE DESTINO">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>KILÓMETROS <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="kilometros" value="{{ old('kilometros', $tramo->kilometros ?? '') }}" required min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>PRECIO TOTAL (Bs) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="precio_total" value="{{ old('precio_total', $tramo->precio_total ?? '') }}" required min="0" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>GASOLINA PROMEDIO (Bs)</label>
                        <input type="number" step="0.01" name="gasolina_promedio" value="{{ old('gasolina_promedio', $tramo->gasolina_promedio ?? '0') }}" min="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>DIESEL PROMEDIO (Bs)</label>
                        <input type="number" step="0.01" name="diesel_promedio" value="{{ old('diesel_promedio', $tramo->diesel_promedio ?? '0') }}" min="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>GAS PROMEDIO (Bs)</label>
                        <input type="number" step="0.01" name="gas_promedio" value="{{ old('gas_promedio', $tramo->gas_promedio ?? '0') }}" min="0">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('tramos.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $tramo ? 'GUARDAR CAMBIOS' : 'REGISTRAR RUTA' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
