@extends('layouts.master')

@section('title', $vehiculo ? 'Editar Vehículo' : 'Nuevo Vehículo - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">{{ $vehiculo ? 'EDITAR' : 'NUEVA' }} UNIDAD</h1>
            <p class="font-bold small text-black uppercase">Registro de Vehículo de Transporte</p>
        </div>
        <a href="{{ route('vehiculos.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="bento-card" style="border: 6px solid #000;">
        <form method="POST" action="{{ $vehiculo ? route('vehiculos.update', $vehiculo->id_vehiculo) : route('vehiculos.store') }}" class="form-bento">
            @csrf
            @if($vehiculo) @method('PUT') @endif

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>PLACA <span class="text-danger">*</span></label>
                        <input type="text" name="placa_vehiculo" value="{{ old('placa_vehiculo', $vehiculo->placa_vehiculo ?? '') }}" required maxlength="20" placeholder="EJ. 1234ABC">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>TIPO <span class="text-danger">*</span></label>
                        <select name="tipo_vehiculo" required>
                            @foreach(['Bus', 'Camión', 'Furgoneta', 'Minibus', 'Camioneta', 'Tractor', 'Otro'] as $tipo)
                                <option value="{{ $tipo }}" {{ old('tipo_vehiculo', $vehiculo->tipo_vehiculo ?? '') == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>MARCA</label>
                        <input type="text" name="marca" value="{{ old('marca', $vehiculo->marca ?? '') }}" placeholder="EJ. SCANIA">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>MODELO</label>
                        <input type="text" name="modelo" value="{{ old('modelo', $vehiculo->modelo ?? '') }}" placeholder="EJ. R500">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>AÑO</label>
                        <input type="number" name="anho" value="{{ old('anho', $vehiculo->anho ?? date('Y')) }}" min="1990" max="{{ date('Y')+1 }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>COLOR</label>
                        <input type="text" name="color" value="{{ old('color', $vehiculo->color ?? '') }}" placeholder="EJ. BLANCO">
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>CAPACIDAD (TN)</label>
                        <input type="number" step="0.01" name="capacidad" value="{{ old('capacidad', $vehiculo->capacidad ?? 0) }}" min="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>KILOMETRAJE</label>
                        <input type="number" name="kilometraje" value="{{ old('kilometraje', $vehiculo->kilometraje ?? 0) }}" min="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label>ESTADO</label>
                        <select name="estado">
                            <option value="1" {{ old('estado', $vehiculo->estado ?? '') == '1' ? 'selected' : '' }}>ACTIVO</option>
                            <option value="2" {{ old('estado', $vehiculo->estado ?? '') == '2' ? 'selected' : '' }}>MANTENIMIENTO</option>
                            <option value="3" {{ old('estado', $vehiculo->estado ?? '') == '3' ? 'selected' : '' }}>VENDIDO</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5">
                <a href="{{ route('vehiculos.index') }}" class="btn-bento btn-bento-outline font-bold" style="border-width:4px!important;text-decoration:none;">CANCELAR</a>
                <button type="submit" class="btn-bento btn-bento-primary px-5 font-bold" style="border-width:4px!important;">
                    <i class="fas fa-save me-2"></i> {{ $vehiculo ? 'GUARDAR CAMBIOS' : 'REGISTRAR UNIDAD' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection