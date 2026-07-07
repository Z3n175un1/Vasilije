@extends('layouts.master')

@section('title', $item ? 'Editar Ítem - VASILIJE' : 'Nuevo Ítem - VASILIJE')

@section('content')
<div class="main-container w-full">
    @if($errors->any())
        <div class="alert alert-danger font-bold mb-4" style="border:3px solid #000;border-radius:0;">
            <i class="fas fa-exclamation-triangle me-2"></i> CORREGIR LOS ERRORES
            <ul class="mb-0 mt-2">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $item ? 'EDITAR' : 'NUEVO' }} ÍTEM</h1>
            <p class="font-bold small text-black uppercase">Catálogo de Productos y Servicios</p>
        </div>
        <a href="{{ route('items.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $item ? route('items.update', $item->id_inventario) : route('items.store') }}" class="form-bento">
            @csrf
            @if($item) @method('PUT') @endif

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>CÓDIGO <span class="text-danger">*</span></label>
                        <input type="text" name="codigo" value="{{ old('codigo', $item->codigo ?? '') }}" required placeholder="CÓDIGO">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group mb-0">
                        <label>NOMBRE <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_producto" value="{{ old('nombre_producto', $item->nombre_producto ?? '') }}" required placeholder="NOMBRE DEL PRODUCTO">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>GRUPO</label>
                        <select name="id_categoria">
                            <option value="">SELECCIONE...</option>
                            @foreach($categorias as $c)
                                <option value="{{ $c->id_categoria }}" {{ old('id_categoria', $item->id_categoria ?? '') == $c->id_categoria ? 'selected' : '' }}>{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>UNIDAD MEDIDA</label>
                        <select name="unidad_medida">
                            @foreach(['UNIDAD', 'LITRO', 'GALÓN', 'KILO', 'CAJA', 'PAR', 'METRO'] as $u)
                                <option value="{{ $u }}" {{ old('unidad_medida', $item->unidad_medida ?? '') == $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>STOCK MÍNIMO</label>
                        <input type="number" step="0.01" name="stock_minimo" value="{{ old('stock_minimo', $item->stock_minimo ?? '0') }}" min="0">
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>DESCRIPCIÓN</label>
                <textarea name="descripcion" rows="3" placeholder="DETALLE...">{{ old('descripcion', $item->descripcion ?? '') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('items.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $item ? 'GUARDAR CAMBIOS' : 'REGISTRAR ÍTEM' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
