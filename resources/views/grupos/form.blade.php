@extends('layouts.master')

@section('title', $grupo ? 'Editar Grupo - VASILIJE' : 'Nuevo Grupo - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $grupo ? 'EDITAR' : 'NUEVO' }} GRUPO</h1>
            <p class="font-bold small text-black uppercase">Categorías y Clasificaciones</p>
        </div>
        <a href="{{ route('grupos.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $grupo ? route('grupos.update', $grupo->id_categoria) : route('grupos.store') }}" class="form-bento">
            @csrf
            @if($grupo) @method('PUT') @endif

            <div class="form-group mb-4">
                <label>NOMBRE <span class="text-danger">*</span></label>
                <input type="text" name="nombre" value="{{ old('nombre', $grupo->nombre ?? '') }}" required placeholder="NOMBRE DE LA CATEGORÍA">
                @error('nombre')
                    <small class="text-danger font-bold">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group mb-4">
                <label>DESCRIPCIÓN</label>
                <textarea name="descripcion" rows="4" placeholder="DESCRIPCIÓN DE LA CATEGORÍA...">{{ old('descripcion', $grupo->descripcion ?? '') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('grupos.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $grupo ? 'GUARDAR CAMBIOS' : 'REGISTRAR GRUPO' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
