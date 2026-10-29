@extends('layouts.master')

@section('title', $grupo ? 'Editar Grupo de Gastos' : 'Nuevo Grupo de Gastos')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $grupo ? 'EDITAR' : 'NUEVO' }} GRUPO DE GASTOS</h1>
            <p class="font-bold small text-black uppercase">Categorías del gasto</p>
        </div>
        <a href="{{ route('grupos-gastos.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    @if($errors->any())
        <div class="bento-card mb-4 p-3" style="border: 4px solid var(--danger, var(--danger)); background: var(--coral-soft);">
            <div class="fw-bold text-danger text-uppercase mb-2">Revise los siguientes errores</div>
            <ul class="mb-0 fw-bold" style="list-style: none; padding-left: 1rem;">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bento-card" style="border: 6px solid var(--line);">
        <form method="POST" action="{{ $grupo ? route('grupos-gastos.update', $grupo->id_grupo_gasto) : route('grupos-gastos.store') }}" class="form-bento">
            @csrf
            @if($grupo) @method('PUT') @endif

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>NOMBRE <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre', $grupo->nombre ?? '') }}" required maxlength="100" placeholder="EJ. TELECOMUNICACIONES, SUELDOS, COMBUSTIBLE">
                        @error('nombre')
                            <small class="text-danger font-bold">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>TIPO DE GASTO (canonico)</label>
                        <select name="tipo_gasto">
                            <option value="">SIN TIPO FIJO</option>
                            @foreach($tiposGasto as $tipo)
                                <option value="{{ $tipo }}" {{ old('tipo_gasto', $grupo->tipo_gasto ?? '') == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                            @endforeach
                        </select>
                        <small class="d-block mt-2 text-black-50">
                            Los gastos de este grupo heredan este tipo: se deriva al guardar.
                        </small>
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>DESCRIPCIÓN</label>
                <textarea name="descripcion" rows="4" placeholder="DESCRIPCIÓN DEL GRUPO...">{{ old('descripcion', $grupo->descripcion ?? '') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('grupos-gastos.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $grupo ? 'GUARDAR CAMBIOS' : 'REGISTRAR GRUPO' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
