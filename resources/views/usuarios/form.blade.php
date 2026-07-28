@extends('layouts.master')

@section('title', $user ? 'Editar Usuario' : 'Nuevo Usuario')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $user ? 'EDITAR' : 'NUEVO' }} USUARIO</h1>
            <p class="font-bold small text-black uppercase">Administración del Sistema</p>
        </div>
        <a href="{{ route('usuarios.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $user ? route('usuarios.update', $user->id_usuario) : route('usuarios.store') }}" class="form-bento">
            @csrf
            @if($user) @method('PUT') @endif

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>USUARIO <span class="text-danger">*</span></label>
                        <input type="text" name="usuario" value="{{ old('usuario', $user->usuario ?? '') }}" required placeholder="NOMBRE DE USUARIO">
                        @error('usuario') <small class="text-danger font-bold">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>EMAIL <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required placeholder="CORREO ELECTRÓNICO">
                        @error('email') <small class="text-danger font-bold">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>CONTRASEÑA {{ $user ? '' : '<span class="text-danger">*</span>' }}</label>
                        <input type="password" name="contrasenha" placeholder="{{ $user ? 'DEJAR VACÍO PARA NO CAMBIAR' : 'CONTRASEÑA' }}" {{ $user ? '' : 'required' }}>
                        @error('contrasenha') <small class="text-danger font-bold">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>ROL <span class="text-danger">*</span></label>
                        <select name="rol" required>
                            <option value="">SELECCIONAR...</option>
                            <option value="admin" {{ old('rol', $user->rol ?? '') == 'admin' ? 'selected' : '' }}>ADMIN</option>
                            <option value="supervisor" {{ old('rol', $user->rol ?? '') == 'supervisor' ? 'selected' : '' }}>SUPERVISOR</option>
                            <option value="operador" {{ old('rol', $user->rol ?? '') == 'operador' ? 'selected' : '' }}>OPERADOR</option>
                            <option value="lectura" {{ old('rol', $user->rol ?? '') == 'lectura' ? 'selected' : '' }}>LECTURA</option>
                        </select>
                        @error('rol') <small class="text-danger font-bold">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>NOMBRES</label>
                        <input type="text" name="nombres" value="{{ old('nombres', $user->nombres ?? '') }}" placeholder="NOMBRE(S)">
                        @error('nombres') <small class="text-danger font-bold">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>APELLIDOS</label>
                        <input type="text" name="apellidos" value="{{ old('apellidos', $user->apellidos ?? '') }}" placeholder="APELLIDO(S)">
                        @error('apellidos') <small class="text-danger font-bold">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>DOCUMENTO IDENTIDAD</label>
                        <input type="text" name="documento_identidad" value="{{ old('documento_identidad', $user->documento_identidad ?? '') }}" placeholder="CÉDULA/NIT">
                        @error('documento_identidad') <small class="text-danger font-bold">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>TELÉFONO</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $user->telefono ?? '') }}" placeholder="TELÉFONO/CELULAR">
                        @error('telefono') <small class="text-danger font-bold">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group mb-3">
                        <label>OBSERVACIONES</label>
                        <textarea name="observaciones" rows="3" placeholder="OBSERVACIONES...">{{ old('observaciones', $user->observaciones ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('usuarios.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $user ? 'GUARDAR CAMBIOS' : 'REGISTRAR USUARIO' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
