@extends('layouts.master')

@section('title', $clasificador ? 'Editar Clasificador' : 'Nuevo Clasificador')

@section('content')
<div class="main-container w-full">

    {{-- HEADER --}}
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 bg-white p-4 rounded-3">
        <div class="header-decoration">
            <h1 class="fs-title mb-2 text-black">
                {{ $clasificador ? 'EDITAR' : 'NUEVO' }} CLASIFICADOR
            </h1>
            <p class="font-bold small text-black uppercase" style="letter-spacing: 0.05em;">
                Catálogo Maestro de Gastos
            </p>
        </div>
        <a href="{{ route('clasificadores.index') }}"
           class="btn-bento btn-bento-outline py-2 px-3 fs-mid font-bold rounded-3 text-decoration-none d-inline-flex align-items-center gap-2">
            <i class="fas fa-arrow-left"></i> VOLVER
        </a>
    </header>

    {{-- ERRORES DE VALIDACIÓN --}}
    @if($errors->any())
        <div class="bento-card mb-4" style="border: 4px solid var(--danger); background: #fff5f5; animation-delay: 0s;">
            <div class="fw-bold text-danger text-uppercase mb-3" style="font-size: 1.15rem;">
                <i class="fas fa-exclamation-triangle me-2"></i>Revise los siguientes errores
            </div>
            <ul class="mb-0 fw-bold" style="list-style: none; padding-left: 1rem;">
                @foreach($errors->all() as $error)
                    <li style="margin-bottom: 0.5rem;">
                        <span style="color: var(--danger);">•</span> {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORMULARIO --}}
    <form method="POST"
          action="{{ $clasificador ? route('clasificadores.update', $clasificador->id_clasificador) : route('clasificadores.store') }}">
        @csrf
        @if($clasificador) @method('PUT') @endif

        {{-- SECCIÓN 1: INFORMACIÓN BÁSICA --}}
        <div class="bento-card" style="border: 6px solid #000;">
            <div class="mb-5 pb-4" style="border-bottom: 3px solid #000;">
                <h2 class="fs-mid font-bold text-black uppercase mb-4" style="letter-spacing: -0.01em;">
                    <i class="fas fa-info-circle me-2" style="color: var(--primary);"></i>Información Básica
                </h2>

                <div class="row g-4">
                    {{-- CODIGO ID
     El codigo es correlativo y lo asigna el servidor: se muestra, no se
     escribe. Editar uno existente tampoco lo cambia, porque los gastos ya
     registrados apuntan a ese codigo. --}}
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="fw-bold text-uppercase text-black mb-3 d-block" style="font-size: 1.05rem; letter-spacing: 0.02em;">
                                CÓDIGO ID
                            </label>

                            @if($clasificador)
                                <input type="text"
                                       class="form-bento-input"
                                       value="{{ $clasificador->codigo }}"
                                       readonly
                                       style="text-transform: uppercase;background:#f0f0f0;font-family:monospace;letter-spacing:1px;">

                                <small class="d-block mt-2 fw-bold" style="color: var(--gray-mid); font-size: 0.95rem;">
                                    No se modifica: los gastos ya registrados apuntan a este código
                                </small>
                            @else
                                {{-- Sin `name`: el backend ni lo lee ni lo
                                     valida, el código lo asigna el servicio. --}}
                                <input type="text"
                                       class="form-bento-input"
                                       value="{{ $codigoSugerido ?? '' }}"
                                       readonly
                                       style="text-transform: uppercase;background:#f0f0f0;font-family:monospace;letter-spacing:1px;">

                                <small class="d-block mt-2 fw-bold" style="color: var(--gray-mid); font-size: 0.95rem;">
                                    Asignado automáticamente al guardar
                                </small>
                            @endif

                            @error('descripcion')
                                <small class="d-block mt-2 fw-bold" style="color:#dc3545;">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    {{-- DESCRIPCIÓN --}}
                    <div class="col-md-9">
                        <div class="form-group mb-0">
                            <label class="fw-bold text-uppercase text-black mb-3 d-block" style="font-size: 1.05rem; letter-spacing: 0.02em;">
                                DESCRIPCIÓN <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="descripcion"
                                   class="form-bento-input @error('descripcion') is-invalid @enderror"
                                   value="{{ old('descripcion', $clasificador->descripcion ?? '') }}"
                                   placeholder="EJ. MANTENIMIENTO MECÁNICO DE CHASIS"
                                   maxlength="200"
                                   required
                                   style="text-transform: uppercase;">
                            @error('descripcion')
                                <small class="d-block mt-2 text-danger fw-bold">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 2: CLASIFICACIÓN --}}
            <div class="mb-5 pb-4" style="border-bottom: 3px solid #000;">
                <h2 class="fs-mid font-bold text-black uppercase mb-4" style="letter-spacing: -0.01em;">
                    <i class="fas fa-tag me-2" style="color: var(--primary);"></i>Clasificación
                </h2>

                <div class="row g-4">
                    {{-- TIPO DE GASTO --}}
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="fw-bold text-uppercase text-black mb-3 d-block" style="font-size: 1.05rem; letter-spacing: 0.02em;">
                                TIPO DE GASTO <span class="text-danger">*</span>
                            </label>
                            <select name="tipo_gasto" class="form-bento-input @error('tipo_gasto') is-invalid @enderror" required>
                                <option value="">SELECCIONE...</option>
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->value }}"
                                        @selected(old('tipo_gasto', $clasificador->tipo_gasto ?? '') === $tipo->value)>
                                        {{ $tipo->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="d-block mt-2 fw-bold" style="color: var(--gray-mid); font-size: 0.95rem;">
                                Valor canónico para reportes de gastos
                            </small>
                            @error('tipo_gasto')
                                <small class="d-block mt-2 text-danger fw-bold">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    {{-- ESTADO --}}
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="fw-bold text-uppercase text-black mb-3 d-block" style="font-size: 1.05rem; letter-spacing: 0.02em;">
                                ESTADO
                            </label>
                            <select name="estado" class="form-bento-input @error('estado') is-invalid @enderror">
                                <option value="ACTIVO"
                                    @selected(old('estado', $clasificador->estado ?? 'ACTIVO') === 'ACTIVO')>
                                    ACTIVO
                                </option>
                                <option value="INACTIVO"
                                    @selected(old('estado', $clasificador->estado ?? '') === 'INACTIVO')>
                                    INACTIVO
                                </option>
                            </select>
                            @error('estado')
                                <small class="d-block mt-2 text-danger fw-bold">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 3: ALCANCE --}}
            <div class="mb-5 pb-4" style="border-bottom: 3px solid #000;">
                <h2 class="fs-mid font-bold text-black uppercase mb-4" style="letter-spacing: -0.01em;">
                    <i class="fas fa-diagram-project me-2" style="color: var(--primary);"></i>Alcance del Clasificador
                </h2>

                <p class="fw-bold mb-4" style="color: var(--gray-mid); font-size: 1rem;">
                    Selecciona dónde se utilizará este clasificador. <span class="text-danger">Debe marcar al menos uno.</span>
                </p>

                <div class="row g-4">
                    {{-- CHECKBOX: AFECTA UNIDAD --}}
                    <div class="col-md-6">
                        <div class="bento-checkbox-card" style="border: 4px solid #000; background: #fff; padding: 2rem;">
                            <div class="form-check mb-0">
                                <input class="form-check-input scope-checkbox"
                                       type="checkbox"
                                       id="afecta_unidad"
                                       name="afecta_unidad"
                                       value="1"
                                       @checked(old('afecta_unidad', $clasificador->afecta_unidad ?? false))
                                       style="width: 1.8rem; height: 1.8rem; border: 3px solid #000; cursor: pointer;">
                                <label class="form-check-label fw-bold" for="afecta_unidad" style="font-size: 1.15rem; cursor: pointer; margin-left: 0.75rem;">
                                    AFECTA A UNIDAD
                                    <span class="d-block small fw-normal mt-2" style="color: var(--gray-mid); text-transform: none; font-size: 0.95rem;">
                                        Aparece al registrar un gasto contra una unidad de la flota.
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- CHECKBOX: AFECTA GENERAL --}}
                    <div class="col-md-6">
                        <div class="bento-checkbox-card" style="border: 4px solid #000; background: #fff; padding: 2rem;">
                            <div class="form-check mb-0">
                                <input class="form-check-input scope-checkbox"
                                       type="checkbox"
                                       id="afecta_general"
                                       name="afecta_general"
                                       value="1"
                                       @checked(old('afecta_general', $clasificador->afecta_general ?? true))
                                       style="width: 1.8rem; height: 1.8rem; border: 3px solid #000; cursor: pointer;">
                                <label class="form-check-label fw-bold" for="afecta_general" style="font-size: 1.15rem; cursor: pointer; margin-left: 0.75rem;">
                                    AFECTA EN FORMA GENERAL
                                    <span class="d-block small fw-normal mt-2" style="color: var(--gray-mid); text-transform: none; font-size: 0.95rem;">
                                        Aparece al registrar un gasto general de la empresa.
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- AVISO DE ALCANCE --}}
                <div id="avisoAlcance"
                     class="mt-4 p-3 fw-bold"
                     style="color: var(--danger); background: #fff5f5; border: 4px solid var(--danger); display: none; border-radius: 8px;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Debe marcar al menos un alcance. Un clasificador sin alcance no se ofrece en ningún formulario.
                </div>
            </div>

            {{-- SECCIÓN 4: OBSERVACIONES --}}
            <div class="mb-4">
                <h2 class="fs-mid font-bold text-black uppercase mb-4" style="letter-spacing: -0.01em;">
                    <i class="fas fa-sticky-note me-2" style="color: var(--primary);"></i>Observaciones
                </h2>

                <div class="form-group mb-0">
                    <textarea name="observaciones"
                              class="form-bento-input @error('observaciones') is-invalid @enderror"
                              rows="4"
                              placeholder="NOTAS INTERNAS...">{{ old('observaciones', $clasificador->observaciones ?? '') }}</textarea>
                    @error('observaciones')
                        <small class="d-block mt-2 text-danger fw-bold">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            {{-- ACCIONES --}}
            <div class="d-flex justify-content-end gap-3 mt-5 pt-4" style="border-top: 3px solid #000;">
                <a href="{{ route('clasificadores.index') }}"
                   class="btn-bento btn-bento-outline fs-mid font-bold text-decoration-none"
                   style="border-width: 4px !important;">
                    CANCELAR
                </a>
                <button type="submit"
                        class="btn-bento btn-bento-primary fs-mid font-bold px-5"
                        style="border-width: 4px !important;">
                    <i class="fas fa-save me-2"></i>
                    {{ $clasificador ? 'GUARDAR CAMBIOS' : 'REGISTRAR CLASIFICADOR' }}
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    /* INPUTS PERSONALIZADOS BENTO STYLE */
    .form-bento-input {
        background: #fff !important;
        color: #000 !important;
        border: 4px solid #000 !important;
        width: 100%;
        padding: 1.25rem 1.75rem;
        font-size: 1.35rem;
        font-weight: 700;
        font-family: inherit;
        border-radius: 0;
        outline: none;
        position: relative;
        z-index: 10;
        pointer-events: auto !important;
        transition: all 0.2s ease;
    }

    .form-bento-input::placeholder {
        color: #999;
        text-transform: uppercase;
    }

    .form-bento-input:focus {
        box-shadow: 0 0 0 4px rgba(47, 44, 121, 0.2) !important;
        border-color: var(--primary) !important;
    }

    .form-bento-input.is-invalid {
        border-color: var(--danger) !important;
    }

    .form-bento-input.is-invalid:focus {
        box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.2) !important;
    }

    .form-bento-input option {
        background: #fff;
        color: #000;
        padding: 1rem;
        font-weight: 700;
    }

    /* CHECKBOX CARDS BENTO */
    .bento-checkbox-card {
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .bento-checkbox-card:hover {
        transform: translate(-2px, -2px);
        box-shadow: 6px 6px 0 #000;
    }

    .bento-checkbox-card .form-check-input {
        cursor: pointer;
        accent-color: var(--primary);
    }

    .bento-checkbox-card .form-check-input:focus {
        box-shadow: 0 0 0 4px rgba(47, 44, 121, 0.2) !important;
    }

    .bento-checkbox-card label {
        user-select: none;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .form-bento-input {
            font-size: 1.15rem;
            padding: 1rem 1.25rem;
        }

        .bento-checkbox-card {
            padding: 1.5rem !important;
        }

        .bento-checkbox-card .form-check-input {
            width: 1.5rem !important;
            height: 1.5rem !important;
        }
    }
</style>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const unidad = document.getElementById('afecta_unidad');
        const general = document.getElementById('afecta_general');
        const aviso = document.getElementById('avisoAlcance');

        function revisarAlcance() {
            aviso.style.display = (!unidad.checked && !general.checked) ? 'block' : 'none';
        }

        unidad.addEventListener('change', revisarAlcance);
        general.addEventListener('change', revisarAlcance);
        revisarAlcance();

        // Validación al enviar formulario
        document.querySelector('form').addEventListener('submit', function (e) {
            if (!unidad.checked && !general.checked) {
                e.preventDefault();
                aviso.style.display = 'block';
                aviso.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });
</script>
@endpush
