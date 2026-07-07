@extends('layouts.master')

@section('title', 'Configuración - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">CONFIGURACIÓN</h1>
            <p class="font-bold small text-black uppercase">Ajustes del Sistema</p>
        </div>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ route('configuracion.update') }}" class="form-bento">
            @csrf
            <h4 class="fw-black mb-4 pb-2 border-bottom border-black d-flex align-items-center gap-2">
                <span class="badge bg-black text-warning px-3 py-2">FLETE</span> CÁLCULO POR TONELADA
            </h4>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>TIPO DE CAMBIO (Bs/$us)</label>
                        <input type="number" step="0.01" name="tipo_cambio" value="{{ old('tipo_cambio', $config['tipo_cambio'] ?? 6.96) }}" min="0" placeholder="6.96">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>PRECIO POR TONELADA ($us)</label>
                        <input type="number" step="0.01" name="precio_tonelada_usd" value="{{ old('precio_tonelada_usd', $config['precio_tonelada_usd'] ?? 13) }}" min="0" placeholder="13">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> GUARDAR CONFIGURACIÓN
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
