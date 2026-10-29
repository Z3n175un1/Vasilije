@extends('layouts.master')

@section('title', 'Configuración')

@php
    $ultimoHistorico = $ultimoHistorico ?? null;

    $textoUltimoHistorico = $ultimoHistorico
        ? \Illuminate\Support\Carbon::parse($ultimoHistorico->fecha)->format('d/m/Y')
            . ' — Bs ' . number_format((float) $ultimoHistorico->tipo_de_cambio, 2, ',', '.')
        : null;

    $textoBcb = !empty($bcb)
        ? 'Bs ' . $bcb['valor_texto'] . ' / USD'
        : null;

    // La opcion MANUAL (digitar el valor a mano) se retiro: es la puerta de
    // entrada a errores de digitacion. Si la base todavia guarda 'MANUAL'
    // (instalaciones viejas), se muestra HISTORICO por defecto.
    $fuenteActual = old('fuente_tipo_cambio', in_array($fuenteTipoCambio, ['BCB', 'HISTORICO'], true) ? $fuenteTipoCambio : 'HISTORICO');
    // Duplicado de $conBcb ya no se usa: la visibilidad del panel dependa
    // EXCLUSIVAMENTE de si la fuente activa es BCB.
    $conBcb       = $fuenteActual === 'BCB';
@endphp

@section('content')
<div class="main-container w-full cfg-page">

    {{-- HEADER (mismo patron que el resto del sistema) --}}
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">CONFIGURACIÓN</h1>
            <p class="font-bold small text-black uppercase mb-0">Parámetros financieros del sistema</p>
        </div>
        <a href="{{ route('documentos.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    {{-- ERRORES DE VALIDACIÓN --}}
    @if($errors->any())
        <div class="bento-card cfg-alert mb-4">
            <div class="fw-bold text-danger text-uppercase mb-2">
                <i class="fas fa-exclamation-triangle me-2"></i>Revisa los siguientes errores
            </div>
            <ul class="mb-0 fw-bold" style="list-style:none;padding-left:0;">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('configuracion.update') }}" class="form-bento">
        @csrf

        {{-- =======================================================
             1. FLETES Y TIPO DE CAMBIO
             ======================================================= --}}
        <section class="bento-card mb-4">
            <div class="cfg-head">
                <span class="cfg-icon"><i class="fas fa-truck"></i></span>
                <div>
                    <h2 class="fs-mid font-bold text-black mb-0">Fletes y tipo de cambio</h2>
                    <p class="cfg-sub">Cómo se calculan los fletes y cuánto vale el dólar en bolivianos</p>
                </div>
            </div>

            <p class="cfg-question">¿Con qué tipo de cambio quieres registrar los fletes?</p>

            <div class="cfg-options" role="radiogroup" aria-label="Fuente del tipo de cambio">
                <label class="cfg-option {{ $fuenteActual === 'BCB' ? 'is-selected' : '' }}" data-fuente="BCB">
                    <input type="radio" name="fuente_tipo_cambio" value="BCB"
                           onchange="cambiarFuenteTipoCambio('BCB')"
                           @checked($fuenteActual === 'BCB')>
                    <span>
                        <span class="cfg-option-title">Usar el valor oficial de hoy</span>
                        <span class="cfg-option-desc">
                            El sistema toma el tipo de cambio oficial del Banco Central de Bolivia y lo actualiza solo.
                            El campo se bloquea para evitar cambios manuales.
                        </span>
                    </span>
                </label>

                <label class="cfg-option {{ $fuenteActual === 'HISTORICO' ? 'is-selected' : '' }}" data-fuente="HISTORICO">
                    <input type="radio" name="fuente_tipo_cambio" value="HISTORICO"
                           onchange="cambiarFuenteTipoCambio('HISTORICO')"
                           @checked($fuenteActual === 'HISTORICO')>
                    <span>
                        <span class="cfg-option-title">Usar el valor oficial del día del flete</span>
                        <span class="cfg-option-desc">
                            Cada día se guarda el valor oficial. Los fines de semana toman el del viernes.
                            Así, cada flete queda con el tipo de cambio que rigió en su fecha.
                        </span>
                    </span>
                </label>
            </div>

            @error('fuente_tipo_cambio')
                <p class="cfg-error mt-3 mb-0">{{ $message }}</p>
            @enderror

            {{-- Valor oficial de hoy (BCB e HISTORICO) --}}
                <div id="bcb-box" class="cfg-bcb {{ $fuenteActual === 'BCB' ? 'is-visible' : '' }}">
                <div>
                    <div class="cfg-bcb-label">Valor oficial del Banco Central</div>
                    <div class="cfg-bcb-value" id="bcb-valor">—</div>
                    <div class="cfg-bcb-date">Vigente al <span id="bcb-fecha">—</span></div>
                    <div class="cfg-bcb-date">Último registro guardado: <span id="hist-valor">—</span></div>
                </div>
                <div class="cfg-bcb-actions">
                    <span id="bcb-error" class="cfg-error d-none"></span>
                    <button type="button" id="bcb-refrescar" class="btn-bento btn-bento-outline" onclick="actualizarBcb(true)">
                        <i class="fas fa-rotate-right me-2"></i> ACTUALIZAR
                    </button>
                </div>
            </div>

            {{-- Consulta de un dia concreto (solo con HISTORICO) --}}
            <div id="hist-consulta" class="cfg-hist {{ $fuenteActual === 'HISTORICO' ? 'is-visible' : '' }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="hist-fecha" class="cfg-hist-label">Consultar el valor de un día</label>
                        <input type="date" id="hist-fecha"
                               class="form-bento-input"
                               max="{{ now()->toDateString() }}"
                               value="{{ $ultimoHistorico->fecha ?? now()->toDateString() }}">
                    </div>
                    <div class="col-md-7">
                        <div class="cfg-hist-result">
                            Valor del <span id="hist-fecha-dia">—</span>:
                            <strong id="hist-valor-dia">—</strong>
                            <span>Bs / USD</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-1">
                {{-- TIPO DE CAMBIO --}}
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label for="tipo_cambio">Tipo de cambio <span class="text-danger">*</span></label>
                        <div class="cfg-unit-wrap">
                        <input type="number"
                               step="0.01"
                               name="tipo_cambio"
                               id="tipo_cambio"
                               class="@error('tipo_cambio') is-invalid @enderror"
                               value="{{ old('tipo_cambio', $tipoCambio) }}"
                               min="0.01"
                               required
                               readonly
                               placeholder="7.50">
                        <span class="cfg-unit">Bs / USD</span>
                    </div>
                    @error('tipo_cambio')
                        <p class="cfg-error mt-2 mb-0">{{ $message }}</p>
                    @enderror
                    <p class="cfg-help" id="tc-help">
                        Cuántos bolivianos equivalen a un dólar. El valor se toma del Banco Central: ya no se digita a mano.
                    </p>
                    <p id="tc-bloqueado" class="cfg-lock">
                        @if($fuenteActual === 'HISTORICO')
                            Bloqueado: cada flete usa el valor oficial de su fecha
                        @else
                            Bloqueado: el valor se toma del Banco Central
                        @endif
                    </p>
                        <span class="cfg-impact"><span>Afecta a</span> Fletes y reportes financieros</span>
                    </div>
                </div>

                {{-- PRECIO POR TONELADA --}}
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label for="precio_tonelada_usd">Precio por tonelada <span class="text-danger">*</span></label>
                        <div class="cfg-unit-wrap">
                            <input type="number"
                                   step="0.01"
                                   name="precio_tonelada_usd"
                                   id="precio_tonelada_usd"
                                   class="@error('precio_tonelada_usd') is-invalid @enderror"
                                   value="{{ old('precio_tonelada_usd', $precioTonelada) }}"
                                   min="0.01"
                                   required
                                   placeholder="150.00">
                            <span class="cfg-unit">Bs</span>
                        </div>
                        @error('precio_tonelada_usd')
                            <p class="cfg-error mt-2 mb-0">{{ $message }}</p>
                        @enderror
                        <p class="cfg-help">
                            Tarifa base por tonelada transportada. Cada ruta puede tener su propio precio, configurado en la sección de rutas.
                        </p>
                        <span class="cfg-impact"><span>Afecta a</span> Cálculo de fletes</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- =======================================================
             2. VALORIZACIÓN DE LA FLOTA
             ======================================================= --}}
        <section class="bento-card mb-4">
            <div class="cfg-head">
                <span class="cfg-icon"><i class="fas fa-scale-balanced"></i></span>
                <div>
                    <h2 class="fs-mid font-bold text-black mb-0">Valorización de la flota</h2>
                    <p class="cfg-sub">Cómo se valoran las unidades dentro del reporte financiero</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label for="valor_flota_por_unidad">Valor por unidad de flota</label>
                        <div class="cfg-unit-wrap">
                            <input type="number"
                                   step="0.01"
                                   name="valor_flota_por_unidad"
                                   id="valor_flota_por_unidad"
                                   class="@error('valor_flota_por_unidad') is-invalid @enderror"
                                   value="{{ old('valor_flota_por_unidad', $valorFlota) }}"
                                   min="0"
                                   placeholder="0.00">
                            <span class="cfg-unit">Bs</span>
                        </div>
                        @error('valor_flota_por_unidad')
                            <p class="cfg-error mt-2 mb-0">{{ $message }}</p>
                        @enderror
                        <p class="cfg-help">
                            El reporte multiplica este valor por la cantidad de unidades para calcular la flota dentro del patrimonio.
                            Si lo dejas en 0, la flota no se incluye.
                        </p>
                        <span class="cfg-impact"><span>Afecta a</span> Patrimonio en el reporte financiero</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <aside class="cfg-note">
                        <div class="cfg-note-title"><i class="fas fa-circle-info"></i> Cambio importante</div>
                        <p class="cfg-note-text">
                            Antes, el reporte usaba un monto fijo de 50.000 Bs por vehículo que no se podía modificar.
                            Ahora ese valor lo defines aquí. El cambio aparece en el próximo reporte que generes.
                        </p>
                    </aside>
                </div>
            </div>
        </section>

        {{-- =======================================================
             3. SALDOS INICIALES
             ======================================================= --}}
        <section class="bento-card mb-4">
            <div class="cfg-head">
                <span class="cfg-icon"><i class="fas fa-building-columns"></i></span>
                <div>
                    <h2 class="fs-mid font-bold text-black mb-0">Saldos iniciales</h2>
                    <p class="cfg-sub">Puntos de partida para los estados de cuenta</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label for="saldo_inicial_proveedores">Saldo inicial de proveedores</label>
                        <div class="cfg-unit-wrap">
                            <input type="number"
                                   step="0.01"
                                   name="saldo_inicial_proveedores"
                                   id="saldo_inicial_proveedores"
                                   class="@error('saldo_inicial_proveedores') is-invalid @enderror"
                                   value="{{ old('saldo_inicial_proveedores', $config['saldo_inicial_proveedores'] ?? 0) }}"
                                   min="0"
                                   placeholder="0.00">
                            <span class="cfg-unit">Bs</span>
                        </div>
                        @error('saldo_inicial_proveedores')
                            <p class="cfg-error mt-2 mb-0">{{ $message }}</p>
                        @enderror
                        <p class="cfg-help">
                            Monto con el que comienza el estado de cuenta de proveedores.
                        </p>
                        <span class="cfg-impact"><span>Afecta a</span> Estado de cuenta de proveedores</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <aside class="cfg-note">
                        <div class="cfg-note-title"><i class="fas fa-calculator"></i> Cómo se calcula el saldo</div>
                        <p class="cfg-note-text">
                            El saldo final parte de este monto y suma los movimientos registrados.
                            Si cambias este valor, el saldo final cambia en la misma cantidad.
                        </p>
                    </aside>
                </div>
            </div>
        </section>

        {{-- ACCIONES --}}
        <div class="cfg-actions">
            <button type="submit" class="btn-bento btn-bento-primary px-5">
                <i class="fas fa-save me-2"></i> GUARDAR CONFIGURACIÓN
            </button>
        </div>
    </form>
</div>

<style>
    /* ============================================================
       PALETA DE CONFIGURACIÓN
       Derivada de los colores del sistema (--primary var(--primary),
       --hover var(--accent), --danger). Texto en azul oscuro en lugar de
       negro puro, fondos tintados suaves y estados claros.
       ============================================================ */
    .cfg-page {
        --cfg-ink: var(--ink);
        --cfg-muted: var(--muted);
        --cfg-line: var(--line);
        --cfg-line-strong: var(--line-strong);
        --cfg-primary: var(--primary);
        --cfg-primary-dark: var(--primary-dark);
        --cfg-primary-soft: var(--primary-soft);
        --cfg-accent: var(--accent);
        --cfg-accent-soft: var(--accent-soft);
        --cfg-accent-ink: var(--accent-ink);
        --cfg-danger: var(--soft-danger);
        --cfg-danger-soft: var(--soft-danger-bg);
        --cfg-surface: var(--surface);
        color: var(--cfg-ink);
    }

    .cfg-page :focus-visible {
        outline: 3px solid var(--cfg-accent);
        outline-offset: 2px;
    }

    /* Tarjetas: borde suave con una franja de color como identidad */
    .cfg-page .bento-card {
        background: #fff;
        border: 1px solid var(--cfg-line);
        border-left: 6px solid var(--cfg-primary);
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(29, 31, 59, 0.06);
        padding: 2rem 2.25rem;
    }

    .cfg-page .bento-card + .bento-card {
        margin-top: 0;
    }

    .cfg-head {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding-bottom: 1.1rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--cfg-line);
    }

    .cfg-page .cfg-head h2 {
        color: var(--cfg-ink);
    }

    .cfg-icon {
        width: 48px;
        height: 48px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--cfg-primary-soft);
        color: var(--cfg-primary);
        border-radius: 12px;
        font-size: 1.1rem;
    }

    .cfg-sub {
        margin: 0.2rem 0 0;
        font-size: 0.95rem;
        font-weight: 500;
        color: var(--cfg-muted);
    }

    .cfg-question {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--cfg-ink);
        margin-bottom: 1rem;
    }

    /* Opciones de fuente */
    .cfg-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
    }

    .cfg-option {
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: 0.9rem;
        margin: 0;
        padding: 1.2rem 1.25rem;
        background: #fff;
        border: 2px solid var(--cfg-line);
        border-radius: 10px;
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }

    .cfg-option:hover {
        border-color: var(--cfg-line-strong);
        box-shadow: 0 4px 14px rgba(29, 31, 59, 0.08);
    }

    .cfg-option.is-selected {
        background: var(--cfg-accent-soft);
        border-color: var(--cfg-primary);
        box-shadow: 0 0 0 1px var(--cfg-primary) inset;
    }

    .cfg-option input[type="radio"] {
        width: 1.25rem;
        height: 1.25rem;
        margin-top: 0.2rem;
        flex-shrink: 0;
        accent-color: var(--cfg-primary);
        cursor: pointer;
    }

    .cfg-option-title {
        display: block;
        font-size: 1rem;
        font-weight: 800;
        color: var(--cfg-ink);
    }

    .cfg-option-desc {
        display: block;
        margin-top: 0.35rem;
        font-size: 0.9rem;
        font-weight: 500;
        line-height: 1.55;
        color: var(--cfg-muted);
    }

    /* Bloque del valor oficial */
    .cfg-bcb {
        display: none;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        margin-top: 1.5rem;
        padding: 1.4rem 1.6rem;
        background: var(--cfg-primary-soft);
        border-radius: 10px;
        border-left: 4px solid var(--cfg-primary);
    }

    .cfg-bcb.is-visible {
        display: flex;
    }

    .cfg-bcb-label {
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--cfg-primary);
    }

    .cfg-bcb-value {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--cfg-ink);
        margin-top: 0.2rem;
    }

    .cfg-bcb-date {
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--cfg-muted);
        margin-top: 0.2rem;
    }

    .cfg-bcb-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    /* Consulta de un dia concreto */
    .cfg-hist {
        display: none;
        margin-top: 1.25rem;
        padding: 1.25rem 1.5rem;
        background: var(--cfg-surface);
        border: 1px dashed var(--cfg-line-strong);
        border-radius: 10px;
    }

    .cfg-hist.is-visible {
        display: block;
    }

    .cfg-hist-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--cfg-ink);
        margin-bottom: 0.5rem;
    }

    .cfg-hist-result {
        font-size: 1rem;
        font-weight: 500;
        color: var(--cfg-muted);
        padding-top: 1.6rem;
    }

    .cfg-hist-result strong {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--cfg-accent-ink);
        margin: 0 0.25rem;
    }

    /* Campos */
    .cfg-page .form-bento label {
        color: var(--cfg-ink);
        font-size: 0.95rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        margin-bottom: 0.6rem;
    }

    .cfg-page .form-bento input {
        border: 2px solid var(--cfg-line-strong) !important;
        border-radius: 8px;
        color: var(--cfg-ink) !important;
        font-size: 1.2rem;
        padding: 0.9rem 1.1rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .cfg-page .form-bento input::placeholder {
        color: var(--line-strong);
    }

    .cfg-page .form-bento input:hover:not(:read-only) {
        border-color: var(--cfg-primary) !important;
    }

    .cfg-page .form-bento input:focus {
        border-color: var(--cfg-primary) !important;
        box-shadow: 0 0 0 4px rgba(47, 44, 121, 0.14) !important;
    }

    .cfg-page .form-bento input[readonly] {
        background: var(--cfg-surface) !important;
        color: var(--cfg-muted) !important;
        cursor: not-allowed;
    }

    .cfg-page .form-bento input.is-invalid {
        border-color: var(--cfg-danger) !important;
        background: var(--cfg-danger-soft) !important;
    }

    .cfg-unit-wrap {
        display: flex;
        align-items: stretch;
    }

    .cfg-unit-wrap input {
        flex: 1;
        min-width: 0;
        border-right: 0 !important;
        border-radius: 8px 0 0 8px !important;
    }

    .cfg-unit {
        display: flex;
        align-items: center;
        padding: 0 1.1rem;
        background: var(--cfg-primary-soft);
        color: var(--cfg-primary);
        border: 2px solid var(--cfg-line-strong);
        border-radius: 0 8px 8px 0;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .cfg-help {
        margin: 0.75rem 0 0;
        font-size: 0.9rem;
        font-weight: 500;
        line-height: 1.55;
        color: var(--cfg-muted);
    }

    .cfg-lock {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin: 0.6rem 0 0;
        padding: 0.25rem 0.6rem;
        background: var(--cfg-primary-soft);
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--cfg-primary);
    }

    .cfg-impact {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem;
        margin-top: 0.9rem;
        padding: 0.4rem 0.8rem;
        background: var(--cfg-accent-soft);
        color: var(--cfg-accent-ink);
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .cfg-impact span {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        opacity: 0.85;
    }

    .cfg-error {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--cfg-danger);
    }

    .cfg-page .bcb-error-slot {
        color: var(--soft-danger);
    }

    /* Notas explicativas */
    .cfg-note {
        height: 100%;
        padding: 1.4rem 1.5rem;
        background: var(--cfg-surface);
        border: 1px solid var(--cfg-line);
        border-radius: 10px;
    }

    .cfg-note-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--cfg-primary);
        margin-bottom: 0.5rem;
    }

    .cfg-note-text {
        margin: 0;
        font-size: 0.92rem;
        font-weight: 500;
        line-height: 1.6;
        color: var(--cfg-ink);
    }

    /* Errores de validacion */
    .cfg-page .cfg-alert {
        background: var(--cfg-danger-soft);
        border: 1px solid var(--coral-soft);
        border-left: 6px solid var(--cfg-danger);
        border-radius: 10px;
    }

    /* Acciones: barra de cierre separada del contenido */
    .cfg-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-top: 0.5rem;
        padding: 1.25rem 1.5rem;
        background: #fff;
        border: 1px solid var(--cfg-line);
        border-radius: 10px;
        position: sticky;
        bottom: 1rem;
        box-shadow: 0 8px 24px rgba(29, 31, 59, 0.08);
    }

    .cfg-page .btn-bento {
        border-radius: 8px;
        border-width: 2px;
        font-size: 1rem;
    }

    .cfg-page .btn-bento-primary {
        background: var(--cfg-primary);
        border-color: var(--cfg-primary);
        color: #fff;
    }

    .cfg-page .btn-bento-primary:hover:not(:disabled) {
        background: var(--cfg-primary-dark);
        border-color: var(--cfg-primary-dark);
        color: #fff;
    }

    .cfg-page .btn-bento-outline {
        border-color: var(--cfg-line-strong);
        color: var(--cfg-primary);
    }

    .cfg-page .btn-bento-outline:hover:not(:disabled) {
        background: var(--cfg-primary-soft);
        border-color: var(--cfg-primary);
        color: var(--cfg-primary-dark);
    }

    .cfg-bcb .btn-bento-outline {
        background: #fff;
    }

    @media (max-width: 768px) {
        .cfg-page .bento-card {
            padding: 1.25rem;
        }

        .cfg-head {
            align-items: flex-start;
        }

        .cfg-icon {
            width: 42px;
            height: 42px;
            font-size: 1rem;
        }

        .cfg-option {
            padding: 1rem;
        }

        .cfg-bcb-value {
            font-size: 1.5rem;
        }

        .cfg-actions {
            position: static;
        }

        .cfg-actions .btn-bento {
            flex: 1;
            justify-content: center;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .cfg-page * {
            transition: none !important;
        }
    }
</style>

<script>
    /* Estado visual de las opciones y bloqueo del campo segun la fuente elegida */
    function cambiarFuenteTipoCambio(fuente) {
        const esBcb = fuente === 'BCB';
        const esHistorico = fuente === 'HISTORICO';
        const bloquear = esBcb || esHistorico;

        document.querySelectorAll('.cfg-option').forEach(card => {
            card.classList.toggle('is-selected', card.dataset.fuente === fuente);
        });

        // El panel del valor oficial de HOY solo tiene sentido con la fuente
        // BCB: con HISTORICO el operador consulta un dia concreto mas abajo,
        // y mostrar el de hoy seria confuso (la fecha del flete manda).
        document.getElementById('bcb-box').classList.toggle('is-visible', esBcb);
        document.getElementById('hist-consulta').classList.toggle('is-visible', esHistorico);
        document.getElementById('tc-bloqueado').classList.toggle('d-none', !bloquear);
        document.getElementById('tc-help').classList.toggle('d-none', bloquear);
        document.getElementById('tipo_cambio').readOnly = bloquear;

        // Texto del aviso segun la fuente activa.
        document.getElementById('tc-bloqueado').textContent = esHistorico
            ? 'Bloqueado: cada flete usa el valor oficial de SU fecha'
            : 'Bloqueado: el valor se toma del Banco Central';

        if (esBcb) {
            actualizarBcb(false);
        }

        if (esHistorico) {
            consultarValorDia(document.getElementById('hist-fecha').value);
        }
    }

    /* Valor oficial de hoy desde el Banco Central */
    async function actualizarBcb(forzar) {
        const valor = document.getElementById('bcb-valor');
        const fecha = document.getElementById('bcb-fecha');
        const error = document.getElementById('bcb-error');
        const boton = document.getElementById('bcb-refrescar');
        const input = document.getElementById('tipo_cambio');

        valor.textContent = 'Cargando…';
        error.classList.add('d-none');

        if (forzar) {
            boton.disabled = true;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> ACTUALIZANDO';
        }

        try {
            const r = await fetch('{{ url('api/tipo-cambio-bcb') }}' + (forzar ? '?force=1' : ''), {
                headers: { 'Accept': 'application/json' }
            });
            const res = await r.json();

            if (res.success && res.data) {
                valor.textContent = 'Bs ' + res.data.valor_texto + ' / USD';
                fecha.textContent = res.data.fecha;
                input.value = res.data.valor;
            } else {
                valor.textContent = '—';
                error.textContent = res.message || 'No se pudo obtener el valor oficial.';
                error.classList.remove('d-none');
            }
        } catch (e) {
            valor.textContent = '—';
            error.textContent = 'No hay conexión con el Banco Central.';
            error.classList.remove('d-none');
        } finally {
            if (forzar) {
                boton.disabled = false;
                boton.innerHTML = '<i class="fas fa-rotate-right me-2"></i> ACTUALIZAR';
            }
        }
    }

    /* Valor que rigio un dia concreto. Fines de semana: viernes de esa semana. */
    async function consultarValorDia(fecha) {
        if (!fecha) {
            return;
        }

        const valor = document.getElementById('hist-valor-dia');
        const dia = document.getElementById('hist-fecha-dia');

        valor.textContent = 'consultando…';
        dia.textContent = fecha;

        try {
            const res = await fetch('{{ url('api/tipo-cambio-para-fecha') }}?fecha=' + encodeURIComponent(fecha), {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (!json.success || !json.data || json.data.valor === null) {
                valor.textContent = 'sin registro';
                return;
            }

            valor.textContent = 'Bs ' + json.data.valor;
            dia.textContent = json.data.fecha_dia;
        } catch (e) {
            valor.textContent = 'error de consulta';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const fuente = document.querySelector('input[name="fuente_tipo_cambio"]:checked')?.value || 'HISTORICO';
        cambiarFuenteTipoCambio(fuente);

        const textoBcb = @json($textoBcb);
        if (textoBcb) {
            document.getElementById('bcb-valor').textContent = textoBcb;
            document.getElementById('bcb-fecha').textContent = @json($bcb['fecha'] ?? '');
        }

        const textoHist = @json($textoUltimoHistorico);
        if (textoHist) {
            document.getElementById('hist-valor').textContent = textoHist;
        }

        document.getElementById('hist-fecha').addEventListener('change', (e) => {
            consultarValorDia(e.target.value);
        });
    });
</script>

@endsection
