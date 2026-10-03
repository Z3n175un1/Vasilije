@extends('layouts.master')

@section('title', 'Configuración')

@section('content')
<div class="main-container w-full">

    {{-- HEADER --}}
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 bg-white p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-2 text-black">CONFIGURACIÓN</h1>
            <p class="font-bold small text-black uppercase" style="letter-spacing: 0.05em;">
                Parámetros financieros del sistema
            </p>
        </div>
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
    <form method="POST" action="{{ route('configuracion.update') }}" class="form-bento">
        @csrf

        {{-- SECCIÓN 1: FLETE / TARIFAS --}}
        <div class="bento-card mb-4" style="border: 6px solid #000;">
            <div class="mb-5 pb-4" style="border-bottom: 3px solid #000;">
                <h2 class="fs-mid font-bold text-black uppercase mb-4" style="letter-spacing: -0.01em;">
                    <i class="fas fa-truck me-2" style="color: var(--primary);"></i>Tarifas de Flete
                </h2>
                <p class="fw-bold mb-0" style="color: var(--gray-mid); font-size: 1rem;">
                    Cálculo por tonelada y tipo de cambio
                </p>
            </div>

            <div class="row g-4 mb-4">
                {{-- TIPO DE CAMBIO --}}
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label class="fw-bold text-uppercase text-black mb-3 d-block" style="font-size: 1.05rem; letter-spacing: 0.02em;">
                            TIPO DE CAMBIO <span class="text-danger">*</span>
                        </label>
                        <div class="input-with-unit">
                            <input type="number"
                                   step="0.01"
                                   name="tipo_cambio"
                                   class="form-bento-input @error('tipo_cambio') is-invalid @enderror"
                                   value="{{ old('tipo_cambio', $tipoCambio) }}"
                                   min="0.01"
                                   required
                                   placeholder="7.50">
                            <span class="input-unit">Bs/USD</span>
                        </div>
                        <small class="d-block mt-2 fw-bold" style="color: var(--gray-mid); font-size: 0.95rem;">
                            Conversión de dólares a bolivianos
                        </small>
                        @error('tipo_cambio')
                            <small class="d-block mt-2 text-danger fw-bold">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- PRECIO POR TONELADA --}}
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label class="fw-bold text-uppercase text-black mb-3 d-block" style="font-size: 1.05rem; letter-spacing: 0.02em;">
                            PRECIO POR TONELADA <span class="text-danger">*</span>
                        </label>
                        <div class="input-with-unit">
                            <input type="number"
                                   step="0.01"
                                   name="precio_tonelada_usd"
                                   class="form-bento-input @error('precio_tonelada_usd') is-invalid @enderror"
                                   value="{{ old('precio_tonelada_usd', $precioTonelada) }}"
                                   min="0.01"
                                   required
                                   placeholder="150.00">
                            <span class="input-unit">Bs</span>
                        </div>
                        <small class="d-block mt-2 fw-bold" style="color: var(--gray-mid); font-size: 0.95rem;">
                            Tarifa base. Cada ruta puede tener su propio precio
                        </small>
                        @error('precio_tonelada_usd')
                            <small class="d-block mt-2 text-danger fw-bold">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2: BALANCE / VALORIZACIÓN --}}
        <div class="bento-card mb-4" style="border: 6px solid #000;">
            <div class="mb-5 pb-4" style="border-bottom: 3px solid #000;">
                <h2 class="fs-mid font-bold text-black uppercase mb-4" style="letter-spacing: -0.01em;">
                    <i class="fas fa-scale-balanced me-2" style="color: var(--primary);"></i>Valorización de Activos
                </h2>
                <p class="fw-bold mb-0" style="color: var(--gray-mid); font-size: 1rem;">
                    Valores para reportes financieros
                </p>
            </div>

            <div class="row g-4 mb-4">
                {{-- VALOR DE LA FLOTA --}}
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label class="fw-bold text-uppercase text-black mb-3 d-block" style="font-size: 1.05rem; letter-spacing: 0.02em;">
                            VALOR POR UNIDAD DE FLOTA
                        </label>
                        <div class="input-with-unit">
                            <input type="number"
                                   step="0.01"
                                   name="valor_flota_por_unidad"
                                   class="form-bento-input @error('valor_flota_por_unidad') is-invalid @enderror"
                                   value="{{ old('valor_flota_por_unidad', $valorFlota) }}"
                                   min="0"
                                   placeholder="0.00">
                            <span class="input-unit">Bs</span>
                        </div>
                        <small class="d-block mt-2 fw-bold" style="color: var(--gray-mid); font-size: 0.95rem;">
                            Se multiplica por cantidad de unidades. Si es cero, la flota no se incluye en patrimonio
                        </small>
                        @error('valor_flota_por_unidad')
                            <small class="d-block mt-2 text-danger fw-bold">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- INFO IMPORTANTE --}}
                <div class="col-md-6">
                    <div class="bento-info-card" style="border: 4px solid var(--primary); background: #f0f4ff; padding: 1.75rem;">
                        <div class="fw-bold text-uppercase mb-2" style="color: var(--primary); font-size: 0.95rem; letter-spacing: 0.02em;">
                            <i class="fas fa-circle-info me-2"></i>Cambio importante
                        </div>
                        <p class="mb-0 fw-bold small" style="color: var(--gray-mid);">
                            Anteriormente, los vehículos se valoraban con un monto <strong>fijo de 50.000 Bs</strong> en el código.
                            Ahora ese valor se define aquí y es configurable. Si es cero, la flota se excluye del cálculo.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 3: CUENTAS / SALDOS INICIALES --}}
        <div class="bento-card mb-4" style="border: 6px solid #000;">
            <div class="mb-5 pb-4" style="border-bottom: 3px solid #000;">
                <h2 class="fs-mid font-bold text-black uppercase mb-4" style="letter-spacing: -0.01em;">
                    <i class="fas fa-building-columns me-2" style="color: var(--primary);"></i>Saldos Iniciales de Cuentas
                </h2>
                <p class="fw-bold mb-0" style="color: var(--gray-mid); font-size: 1rem;">
                    Valores de referencia para estado de cuentas
                </p>
            </div>

            <div class="row g-4">
                {{-- SALDO INICIAL PROVEEDORES --}}
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label class="fw-bold text-uppercase text-black mb-3 d-block" style="font-size: 1.05rem; letter-spacing: 0.02em;">
                            SALDO INICIAL DE PROVEEDORES
                        </label>
                        <div class="input-with-unit">
                            <input type="number"
                                   step="0.01"
                                   name="saldo_inicial_proveedores"
                                   class="form-bento-input @error('saldo_inicial_proveedores') is-invalid @enderror"
                                   value="{{ old('saldo_inicial_proveedores', $config['saldo_inicial_proveedores'] ?? 0) }}"
                                   min="0"
                                   placeholder="0.00">
                            <span class="input-unit">Bs</span>
                        </div>
                        <small class="d-block mt-2 fw-bold" style="color: var(--gray-mid); font-size: 0.95rem;">
                            Punto de partida del estado de cuenta. El saldo final se calcula sumando movimientos
                        </small>
                        @error('saldo_inicial_proveedores')
                            <small class="d-block mt-2 text-danger fw-bold">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- NOTA TÉCNICA --}}
                <div class="col-md-6">
                    <div class="bento-info-card" style="border: 4px solid var(--info); background: #e3f2fd; padding: 1.75rem;">
                        <div class="fw-bold text-uppercase mb-2" style="color: var(--info); font-size: 0.95rem; letter-spacing: 0.02em;">
                            <i class="fas fa-triangle-exclamation me-2"></i>Nota técnica
                        </div>
                        <p class="mb-0 fw-bold small" style="color: var(--gray-dark);">
                             El saldo se derivan de los movimientos más este valor inicial.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ACCIONES --}}
        <div class="d-flex justify-content-end gap-3 mt-5 pt-4" style="border-top: 3px solid #000;">
            <a href="{{ route('documentos.index') }}"
               class="btn-bento btn-bento-outline fs-mid font-bold text-decoration-none"
               style="border-width: 4px !important;">
                VOLVER
            </a>
            <button type="submit" class="btn-bento btn-bento-primary fs-mid font-bold px-5" style="border-width: 4px !important;">
                <i class="fas fa-save me-2"></i> GUARDAR CONFIGURACIÓN
            </button>
        </div>
    </form>
</div>

<style>
    /* ====================================================
       INPUTS CON UNIDADES
       ==================================================== */
    .input-with-unit {
        position: relative;
        display: flex;
        align-items: stretch;
    }

    .input-with-unit .form-bento-input {
        flex: 1;
        border-right: none !important;
        border-radius: 0 !important;
    }

    .input-unit {
        display: flex;
        align-items: center;
        justify-content: center;

        padding: 0 1.5rem;

        background: #f5f5f5;
        color: #000;

        border: 4px solid #000;
        border-left: none;

        font-weight: 800;
        font-size: 0.95rem;
        text-transform: uppercase;

        white-space: nowrap;

        letter-spacing: 0.05em;
    }

    @media (max-width: 768px) {
        .input-unit {
            padding: 0 0.75rem;
            font-size: 0.85rem;
        }
    }

    /* ====================================================
       CARDS DE INFORMACIÓN
       ==================================================== */
    .bento-info-card {
        display: flex;
        flex-direction: column;
        justify-content: center;

        transition: all 0.2s ease;
    }

    .bento-info-card:hover {
        transform: translate(-2px, -2px);
        box-shadow: 4px 4px 0 rgba(0, 0, 0, 0.15);
    }

    .bento-info-card code {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        font-size: 0.9rem;
    }

    /* ====================================================
       INPUTS PERSONALIZADOS BENTO STYLE
       ==================================================== */
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

    /* ====================================================
       RESPONSIVE
       ==================================================== */
    @media (max-width: 768px) {
        .form-bento-input {
            font-size: 1.15rem;
            padding: 1rem 1.25rem;
        }

        .bento-info-card {
            margin-top: 1rem;
        }

        .input-with-unit {
            flex-direction: column;
        }

        .input-with-unit .form-bento-input {
            border-right: 4px solid #000 !important;
            border-radius: 0 !important;
        }

        .input-unit {
            border-left: 4px solid #000 !important;
            border-top: none;
            padding: 0.75rem;
        }
    }
</style>

@endsection
