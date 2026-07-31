@extends('layouts.master')

@section('title', $proveedor ? 'Editar Proveedor' : 'Nuevo Proveedor')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $proveedor ? 'EDITAR' : 'NUEVO' }} PROVEEDOR</h1>
            <p class="font-bold small text-black uppercase">Control de Proveedores</p>
        </div>
        <a href="{{ route('proveedores.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $proveedor ? route('proveedores.update', $proveedor->id_proveedor) : route('proveedores.store') }}" class="form-bento">
            @csrf
            @if($proveedor) @method('PUT') @endif

            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="form-group mb-0">
                        <label>RAZÓN SOCIAL <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_proveedor" value="{{ old('nombre_proveedor', $proveedor->nombre_proveedor ?? '') }}" required placeholder="NOMBRE O RAZÓN SOCIAL">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>NIT</label>
                        <input type="text" name="nit_ci" value="{{ old('nit_ci', $proveedor->nit_ci ?? '') }}" placeholder="NIT / CI">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>CONTACTO</label>
                        <input type="text" name="contacto" value="{{ old('contacto', $proveedor->contacto ?? '') }}" placeholder="NOMBRE DEL CONTACTO">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>TELÉFONO</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $proveedor->telefono ?? '') }}" placeholder="TELÉFONO">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label>EMAIL</label>
                        <input type="email" name="email" value="{{ old('email', $proveedor->email ?? '') }}" placeholder="CORREO">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="form-group mb-0">
                        <label>DIRECCIÓN</label>
                        <input type="text" name="direccion" value="{{ old('direccion', $proveedor->direccion ?? '') }}" placeholder="DIRECCIÓN">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>TIPO DE PROVEEDOR</label>
                        <select name="tipo_proveedor">
                            @foreach(['GENERAL', 'AUTOPARTES', 'COMBUSTIBLE', 'LUBRICANTES', 'SERVICIOS', 'ALIMENTOS', 'OTROS'] as $tipo)
                                <option value="{{ $tipo }}" {{ old('tipo_proveedor', $proveedor->tipo_proveedor ?? 'GENERAL') == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('proveedores.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $proveedor ? 'GUARDAR CAMBIOS' : 'REGISTRAR PROVEEDOR' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
