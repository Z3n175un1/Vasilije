@extends('layouts.master')

@section('title', $producto ? 'Editar Producto - VASILIJE' : 'Nuevo Producto - VASILIJE')

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
            <h1 class="fs-title mb-0 text-black">{{ $producto ? 'EDITAR' : 'NUEVO' }} PRODUCTO</h1>
            <p class="font-bold small text-black uppercase">Control de Inventario y Repuestos</p>
        </div>
        <a href="{{ route('almacen.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $producto ? route('almacen.update', $producto->id_inventario) : route('almacen.store') }}" class="form-bento">
            @csrf
            @if($producto) @method('PUT') @endif

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>CÓDIGO <span class="text-danger">*</span></label>
                        <input type="text" name="codigo" value="{{ old('codigo', $producto->codigo ?? '') }}" required placeholder="CÓDIGO DEL PRODUCTO">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group mb-0">
                        <label>NOMBRE <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_producto" value="{{ old('nombre_producto', $producto->nombre_producto ?? '') }}" required placeholder="NOMBRE DEL PRODUCTO">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>CATEGORÍA <span class="text-danger">*</span></label>
                        <select name="categoria" required>
                            <option value="">SELECCIONE...</option>
                            @foreach($categorias as $c)
                                <option value="{{ $c->nombre }}" {{ old('categoria', $producto->categoria ?? '') == $c->nombre ? 'selected' : '' }}>{{ $c->nombre }}</option>
                            @endforeach
                            <option value="OTRA" {{ old('categoria', $producto->categoria ?? '') == 'OTRA' ? 'selected' : '' }}>OTRA</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>UNIDAD DE MEDIDA <span class="text-danger">*</span></label>
                        <select name="unidad_medida" required>
                            @foreach(['UNIDAD', 'LITRO', 'GALÓN', 'KILO', 'CAJA', 'PAR', 'METRO', 'LIBRA', 'TAMBOR'] as $u)
                                <option value="{{ $u }}" {{ old('unidad_medida', $producto->unidad_medida ?? '') == $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>MARCA</label>
                        <input type="text" name="marca" value="{{ old('marca', $producto->marca ?? '') }}" placeholder="MARCA">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>STOCK ACTUAL <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="stock_actual" value="{{ old('stock_actual', $producto->stock_actual ?? '0') }}" required min="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>STOCK MÍNIMO</label>
                        <input type="number" step="0.01" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo ?? '0') }}" min="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>PRECIO COMPRA (Bs)</label>
                        <input type="number" step="0.01" name="precio_compra" value="{{ old('precio_compra', $producto->precio_compra ?? '0') }}" min="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>PROVEEDOR</label>
                        <select name="id_proveedor">
                            <option value="">SELECCIONE...</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id_proveedor }}" {{ old('id_proveedor', $producto->id_proveedor ?? '') == $p->id_proveedor ? 'selected' : '' }}>{{ $p->nombre_proveedor }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>UBICACIÓN</label>
                        <input type="text" name="ubicacion_almacen" value="{{ old('ubicacion_almacen', $producto->ubicacion_almacen ?? '') }}" placeholder="EJ. ESTANTE A-1">
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>DESCRIPCIÓN</label>
                <textarea name="descripcion" rows="3" placeholder="DETALLE DEL PRODUCTO...">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('almacen.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $producto ? 'GUARDAR CAMBIOS' : 'REGISTRAR PRODUCTO' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
