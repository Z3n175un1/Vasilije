@extends('layouts.master')

@section('title', $personal ? 'Editar Personal - VASILIJE' : 'Nuevo Personal - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $personal ? 'EDITAR' : 'NUEVO' }} PERSONAL</h1>
            <p class="font-bold small text-black uppercase">Gestión de Conductores y Empleados</p>
        </div>
        <a href="{{ route('personal.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $personal ? route('personal.update', $personal->id_personal) : route('personal.store') }}" class="form-bento">
            @csrf
            @if($personal) @method('PUT') @endif

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>NOMBRES <span class="text-danger">*</span></label>
                        <input type="text" name="nombres" value="{{ old('nombres', $personal->nombres ?? '') }}" required placeholder="NOMBRES">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>APELLIDOS <span class="text-danger">*</span></label>
                        <input type="text" name="apellidos" value="{{ old('apellidos', $personal->apellidos ?? '') }}" required placeholder="APELLIDOS">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>C.I.</label>
                        <input type="text" name="ci" value="{{ old('ci', $personal->ci ?? '') }}" placeholder="NÚMERO DE CÉDULA">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>CARGO <span class="text-danger">*</span></label>
                        <select name="cargo" required>
                            @foreach(['Conductor', 'Ayudante', 'Mecánico', 'Administrativo', 'Supervisor', 'Otro'] as $cargo)
                                <option value="{{ $cargo }}" {{ old('cargo', $personal->cargo ?? '') == $cargo ? 'selected' : '' }}>{{ $cargo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>TELÉFONO</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $personal->telefono ?? '') }}" placeholder="TELÉFONO/CELULAR">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>LICENCIA</label>
                        <input type="text" name="licencia" value="{{ old('licencia', $personal->licencia ?? '') }}" placeholder="N° LICENCIA">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>SUELDO (Bs)</label>
                        <input type="number" step="0.01" name="sueldo" value="{{ old('sueldo', $personal->sueldo ?? '') }}" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>ESTADO</label>
                        <select name="estado">
                            <option value="1" {{ old('estado', $personal->estado ?? '1') == '1' ? 'selected' : '' }}>ACTIVO</option>
                            <option value="0" {{ old('estado', $personal->estado ?? '') == '0' ? 'selected' : '' }}>INACTIVO</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>EMAIL</label>
                        <input type="email" name="email" value="{{ old('email', $personal->email ?? '') }}" placeholder="CORREO ELECTRÓNICO">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>DIRECCIÓN</label>
                        <input type="text" name="direccion" value="{{ old('direccion', $personal->direccion ?? '') }}" placeholder="DIRECCIÓN">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('personal.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $personal ? 'GUARDAR CAMBIOS' : 'REGISTRAR PERSONAL' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
