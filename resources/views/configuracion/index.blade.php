@extends('layouts.master')

@section('title', 'Configuración')

@section('content')
<div class="main-container w-full">

    {{-- HEADER --}}
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 bg-white p-4 rounded-3 shadow-heavy">
        <div>
            <h1 class="fs-title mb-2 text-black">CONFIGURACIÓN</h1>
            <p class="font-bold small text-black mb-0" style="letter-spacing: 0.05em;">
                Parámetros financieros del sistema
            </p>
        </div>
    </header>

    {{-- ERRORES DE VALIDACIÓN --}}
    @if($errors->any())
        <div class="cfg-alert mb-4" role="alert">
            <div class="cfg-alert-title">
                <i class="fas fa-exclamation-triangle"></i> Revisa los siguientes errores
            </div>
            <ul class="cfg-alert-list">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('configuracion.update') }}">
        @csrf

        {{-- ==========================================
             SECCIÓN 1: FLETES Y TIPO DE CAMBIO
             ========================================== --}}
        <section class="cfg-section">
            <header class="cfg-section-head">
                <div class="cfg-section-icon"><i class="fas fa-truck"></i></div>
                <div>
                    <h2 class="cfg-section-title">Fletes y tipo de cambio</h2>
                    <p class="cfg-section-sub">Cómo se calculan los fletes y cuánto vale el dólar en bolivianos</p>
                </div>
            </header>

            <div class="cfg-section-body">

                <div class="cfg-question">¿Con qué tipo de cambio quieres registrar los fletes?</div>

                <div class="option-grid" role="radiogroup" aria-label="Fuente del tipo de cambio">
                    <label class="option-card {{ old('fuente_tipo_cambio', $fuenteTipoCambio) === 'MANUAL' ? 'is-selected' : '' }}"
                           data-fuente="MANUAL">
                        <input type="radio"
                               name="fuente_tipo_cambio"
                               value="MANUAL"
                               onchange="cambiarFuenteTipoCambio('MANUAL')"
                               @checked(old('fuente_tipo_cambio', $fuenteTipoCambio) === 'MANUAL')>
                        <span class="option-body">
                            <span class="option-title">Ingresarlo manualmente</span>
                            <span class="option-desc">
                                Tú decides el valor y lo escribes en el campo de abajo. Los fletes se registran con ese monto.
                            </span>
                        </span>
                    </label>

                    <label class="option-card {{ old('fuente_tipo_cambio', $fuenteTipoCambio) === 'BCB' ? 'is-selected' : '' }}"
                           data-fuente="BCB">
                        <input type="radio"
                               name="fuente_tipo_cambio"
                               value="BCB"
                               onchange="cambiarFuenteTipoCambio('BCB')"
                               @checked(old('fuente_tipo_cambio', $fuenteTipoCambio) === 'BCB')>
                        <span class="option-body">
                            <span class="option-title">Usar el valor oficial del día</span>
                            <span class="option-desc">
                                El sistema toma el tipo de cambio oficial del Banco Central de Bolivia y lo actualiza solo.
                                El campo se bloquea para evitar cambios manuales.
                            </span>
                        </span>
                    </label>
                </div>

                @error('fuente_tipo_cambio')
                    <div class="cfg-error mt-3">{{ $message }}</div>
                @enderror

                {{-- Valor oficial BCB --}}
                <div id="bcb-box" class="bcb-box {{ old('fuente_tipo_cambio', $fuenteTipoCambio) === 'BCB' ? 'is-visible' : '' }}">
                    <div>
                        <div class="bcb-label">Valor oficial BCB</div>
                        <div class="bcb-value" id="bcb-valor">—</div>
                        <div class="bcb-date">Vigente al <span id="bcb-fecha">—</span></div>
                    </div>
                    <div class="bcb-actions">
                        <span id="bcb-error" class="cfg-error d-none"></span>
                        <button type="button" id="bcb-refrescar" class="btn-bento btn-bento-outline" onclick="actualizarBcb(true)">
                            <i class="fas fa-rotate-right me-2"></i> ACTUALIZAR
                        </button>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    {{-- TIPO DE CAMBIO --}}
                    <div class="col-md-6">
                        <div class="cfg-field">
                            <label for="tipo_cambio" class="cfg-field-label">
                                Tipo de cambio <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-unit">
                                <input type="number"
                                       step="0.01"
                                       name="tipo_cambio"
                                       id="tipo_cambio"
                                       class="form-bento-input @error('tipo_cambio') is-invalid @enderror"
                                       value="{{ old('tipo_cambio', $tipoCambio) }}"
                                       min="0.01"
                                       required
                                       placeholder="7.50">
                                <span class="input-unit">Bs / USD</span>
                            </div>
                            @error('tipo_cambio')
                                <div class="cfg-error mt-2">{{ $message }}</div>
                            @enderror
                            <p class="cfg-help">
                                Cuántos bolivianos equivalen a un dólar. Se usa para convertir a bolivianos los montos que están en dólares.
                            </p>
                            <p id="tc-bloqueado" class="cfg-lock d-none">
                                <i class="fas fa-lock"></i> Bloqueado: se actualiza con el valor oficial
                            </p>
                            <div class="cfg-impact">
                                <span>Afecta a</span> Fletes y reportes financieros
                            </div>
                        </div>
                    </div>

                    {{-- PRECIO POR TONELADA --}}
                    <div class="col-md-6">
                        <div class="cfg-field">
                            <label for="precio_tonelada_usd" class="cfg-field-label">
                                Precio por tonelada <span class="text-danger">*</span>
                            </label>
                            <div class="input-with-unit">
                                <input type="number"
                                       step="0.01"
                                       name="precio_tonelada_usd"
                                       id="precio_tonelada_usd"
                                       class="form-bento-input @error('precio_tonelada_usd') is-invalid @enderror"
                                       value="{{ old('precio_tonelada_usd', $precioTonelada) }}"
                                       min="0.01"
                                       required
                                       placeholder="150.00">
                                <span class="input-unit">Bs</span>
                            </div>
                            @error('precio_tonelada_usd')
                                <div class="cfg-error mt-2">{{ $message }}</div>
                            @enderror
                            <p class="cfg-help">
                                Tarifa base para calcular el flete de cada tonelada transportada. Cada ruta puede tener su propio precio, configurado en la sección de rutas.
                            </p>
                            <div class="cfg-impact">
                                <span>Afecta a</span> Cálculo de fletes
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ==========================================
             SECCIÓN 2: VALORIZACIÓN DE LA FLOTA
             ========================================== --}}
        <section class="cfg-section">
            <header class="cfg-section-head">
                <div class="cfg-section-icon"><i class="fas fa-scale-balanced"></i></div>
                <div>
                    <h2 class="cfg-section-title">Valorización de la flota</h2>
                    <p class="cfg-section-sub">Cómo se valoran las unidades dentro del reporte financiero</p>
                </div>
            </header>

            <div class="cfg-section-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="cfg-field">
                            <label for="valor_flota_por_unidad" class="cfg-field-label">
                                Valor por unidad de flota
                            </label>
                            <div class="input-with-unit">
                                <input type="number"
                                       step="0.01"
                                       name="valor_flota_por_unidad"
                                       id="valor_flota_por_unidad"
                                       class="form-bento-input @error('valor_flota_por_unidad') is-invalid @enderror"
                                       value="{{ old('valor_flota_por_unidad', $valorFlota) }}"
                                       min="0"
                                       placeholder="0.00">
                                <span class="input-unit">Bs</span>
                            </div>
                            @error('valor_flota_por_unidad')
                                <div class="cfg-error mt-2">{{ $message }}</div>
                            @enderror
                            <p class="cfg-help">
                                El reporte financiero multiplica este valor por la cantidad de unidades de la flota para calcular su peso dentro del patrimonio.
                                Si lo dejas en 0, la flota no se incluye en el patrimonio.
                            </p>
                            <div class="cfg-impact">
                                <span>Afecta a</span> Patrimonio en el reporte financiero
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <aside class="cfg-note cfg-note-info">
                            <div class="cfg-note-title">
                                <i class="fas fa-circle-info"></i> Cambio importante
                            </div>
                            <p class="cfg-note-text">
                                Antes, el reporte usaba un monto fijo de 50.000 Bs por cada vehículo y no se podía modificar.
                                Ahora ese valor lo defines tú aquí. El cambio se verá en el próximo reporte que generes.
                            </p>
                        </aside>
                    </div>
                </div>
            </div>
        </section>

        {{-- ==========================================
             SECCIÓN 3: SALDOS INICIALES
             ========================================== --}}
        <section class="cfg-section">
            <header class="cfg-section-head">
                <div class="cfg-section-icon"><i class="fas fa-building-columns"></i></div>
                <div>
                    <h2 class="cfg-section-title">Saldos iniciales</h2>
                    <p class="cfg-section-sub">Puntos de partida para los estados de cuenta</p>
                </div>
            </header>

            <div class="cfg-section-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="cfg-field">
                            <label for="saldo_inicial_proveedores" class="cfg-field-label">
                                Saldo inicial de proveedores
                            </label>
                            <div class="input-with-unit">
                                <input type="number"
                                       step="0.01"
                                       name="saldo_inicial_proveedores"
                                       id="saldo_inicial_proveedores"
                                       class="form-bento-input @error('saldo_inicial_proveedores') is-invalid @enderror"
                                       value="{{ old('saldo_inicial_proveedores', $config['saldo_inicial_proveedores'] ?? 0) }}"
                                       min="0"
                                       placeholder="0.00">
                                <span class="input-unit">Bs</span>
                            </div>
                            @error('saldo_inicial_proveedores')
                                <div class="cfg-error mt-2">{{ $message }}</div>
                            @enderror
                            <p class="cfg-help">
                                Monto con el que comienza el estado de cuenta de proveedores.
                            </p>
                            <div class="cfg-impact">
                                <span>Afecta a</span> Estado de cuenta de proveedores
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <aside class="cfg-note cfg-note-neutral">
                            <div class="cfg-note-title">
                                <i class="fas fa-calculator"></i> Cómo se calcula el saldo
                            </div>
                            <p class="cfg-note-text">
                                El saldo final parte de este monto inicial y suma los movimientos registrados.
                                Si cambias este valor, el saldo final cambia en la misma cantidad.
                            </p>
                        </aside>
                    </div>
                </div>
            </div>
        </section>

        {{-- ACCIONES --}}
        <div class="cfg-actions">
            <a href="{{ route('documentos.index') }}" class="btn-bento btn-bento-outline text-decoration-none">
                VOLVER
            </a>
            <button type="submit" class="btn-bento btn-bento-primary px-5">
                <i class="fas fa-save me-2"></i> GUARDAR CONFIGURACIÓN
            </button>
        </div>
    </form>
</div>

<style>
    /* ============================================================
       SECCIONES
       ============================================================ */
    .cfg-section {
        background: #fff;
        border: 6px solid #000;
        margin-bottom: 2rem;
    }

    .cfg-section-head {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding: 1.5rem 2rem;
        border-bottom: 4px solid #000;
    }

    .cfg-section-icon {
        width: 56px;
        height: 56px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--primary, #2f2c79);
        color: #fff;
        font-size: 1.2rem;
        border: 4px solid #000;
    }

    .cfg-section-title {
        font-size: 1.25rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: -0.01em;
        margin: 0;
        color: #000;
    }

    .cfg-section-sub {
        margin: 0.25rem 0 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--gray-mid, #666);
    }

    .cfg-section-body {
        padding: 2rem;
    }

    .cfg-question {
        font-size: 1.05rem;
        font-weight: 800;
        color: #000;
        margin-bottom: 1rem;
    }

    /* ============================================================
       OPCIONES (RADIO COMO TARJETA)
       ============================================================ */
    .option-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .option-card {
        display: flex;
        align-items: flex-start;
        gap: 1.1rem;
        padding: 1.5rem;
        background: #fff;
        border: 4px solid #000;
        cursor: pointer;
        transition: background 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        margin: 0;
    }

    .option-card:hover {
        box-shadow: 5px 5px 0 #000;
    }

    .option-card.is-selected {
        background: #eef0ff;
        border-color: var(--primary, #2f2c79);
        box-shadow: 6px 6px 0 var(--primary, #2f2c79);
    }

    .option-card input[type="radio"] {
        width: 1.4rem;
        height: 1.4rem;
        margin-top: 0.2rem;
        flex-shrink: 0;
        accent-color: var(--primary, #2f2c79);
        cursor: pointer;
    }

    .option-title {
        display: block;
        font-size: 1.05rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        color: #000;
    }

    .option-desc {
        display: block;
        margin-top: 0.4rem;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.5;
        color: var(--gray-mid, #666);
    }

    /* ============================================================
       BLOQUE VALOR OFICIAL BCB
       ============================================================ */
    .bcb-box {
        display: none;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        margin-top: 1.5rem;
        padding: 1.5rem;
        background: var(--primary, #2f2c79);
        color: #fff;
        border: 4px solid #000;
    }

    .bcb-box.is-visible {
        display: flex;
    }

    .bcb-label {
        font-size: 0.8rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.85;
    }

    .bcb-value {
        font-size: 1.9rem;
        font-weight: 900;
        margin-top: 0.25rem;
    }

    .bcb-date {
        font-size: 0.9rem;
        font-weight: 600;
        opacity: 0.85;
        margin-top: 0.25rem;
    }

    .bcb-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .bcb-box .btn-bento-outline {
        background: #fff;
        color: #000;
    }

    .bcb-box .btn-bento-outline:hover:not(:disabled) {
        background: #000;
        color: #fff;
    }

    /* ============================================================
       CAMPOS
       ============================================================ */
    .cfg-field-label {
        display: block;
        font-size: 1rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        color: #000;
        margin-bottom: 0.85rem;
    }

    .input-with-unit {
        display: flex;
        align-items: stretch;
    }

    .form-bento-input {
        flex: 1;
        min-width: 0;
        width: 100%;
        background: #fff;
        color: #000;
        border: 4px solid #000;
        border-radius: 0;
        padding: 1rem 1.25rem;
        font-size: 1.25rem;
        font-weight: 700;
        font-family: inherit;
        outline: none;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .input-with-unit .form-bento-input {
        border-right: none;
    }

    .form-bento-input::placeholder {
        color: #aaa;
    }

    .form-bento-input:focus {
        border-color: var(--primary, #2f2c79);
        box-shadow: 0 0 0 4px rgba(47, 44, 121, 0.2);
    }

    .form-bento-input[readonly] {
        background: #f0f0f0;
        color: #555;
        cursor: not-allowed;
    }

    .form-bento-input.is-invalid {
        border-color: var(--danger, #dc3545);
    }

    .input-unit {
        display: flex;
        align-items: center;
        padding: 0 1.25rem;
        background: #000;
        color: #fff;
        border: 4px solid #000;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .cfg-help {
        margin: 0.85rem 0 0;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.5;
        color: var(--gray-mid, #666);
    }

    .cfg-lock {
        margin: 0.6rem 0 0;
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--primary, #2f2c79);
    }

    .cfg-impact {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
        padding: 0.4rem 0.85rem;
        background: #eef0ff;
        border: 2px solid var(--primary, #2f2c79);
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--primary, #2f2c79);
    }

    .cfg-impact span {
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .cfg-error {
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--danger, #dc3545);
    }

    /* ============================================================
       NOTAS
       ============================================================ */
    .cfg-note {
        height: 100%;
        padding: 1.5rem;
        border: 4px solid #000;
        background: #fafafa;
    }

    .cfg-note-info {
        border-color: var(--primary, #2f2c79);
        background: #f0f4ff;
    }

    .cfg-note-info .cfg-note-title {
        color: var(--primary, #2f2c79);
    }

    .cfg-note-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.95rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 0.75rem;
        color: #000;
    }

    .cfg-note-text {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.55;
        color: #333;
    }

    /* ============================================================
       ALERTA DE ERRORES
       ============================================================ */
    .cfg-alert {
        background: #fff5f5;
        border: 4px solid var(--danger, #dc3545);
        border-left-width: 10px;
        padding: 1.25rem 1.5rem;
    }

    .cfg-alert-title {
        font-size: 1rem;
        font-weight: 900;
        text-transform: uppercase;
        color: var(--danger, #dc3545);
        margin-bottom: 0.5rem;
    }

    .cfg-alert-list {
        margin: 0;
        padding-left: 1.25rem;
        font-weight: 700;
        color: #000;
    }

    /* ============================================================
       ACCIONES
       ============================================================ */
    .cfg-actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        flex-wrap: wrap;
        padding-top: 1rem;
    }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 768px) {
        .cfg-section {
            border-width: 4px;
        }

        .cfg-section-head {
            padding: 1.25rem;
            align-items: flex-start;
        }

        .cfg-section-icon {
            width: 46px;
            height: 46px;
            font-size: 1rem;
        }

        .cfg-section-body {
            padding: 1.25rem;
        }

        .form-bento-input {
            font-size: 1.1rem;
            padding: 0.85rem 1rem;
        }

        .input-unit {
            padding: 0 0.85rem;
        }

        .bcb-value {
            font-size: 1.5rem;
        }

        .cfg-actions .btn-bento {
            flex: 1;
            justify-content: center;
        }
    }
</style>

<script>
    function cambiarFuenteTipoCambio(fuente) {
        const esBcb = fuente === 'BCB';
        const input = document.getElementById('tipo_cambio');

        document.querySelectorAll('.option-card').forEach(card => {
            card.classList.toggle('is-selected', card.dataset.fuente === fuente);
        });

        document.getElementById('bcb-box').classList.toggle('is-visible', esBcb);
        document.getElementById('tc-bloqueado').classList.toggle('d-none', !esBcb);
        input.readOnly = esBcb;

        if (esBcb) {
            actualizarBcb(false);
        }
    }

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
            error.textContent = 'Error de conexión con el Banco Central.';
            error.classList.remove('d-none');
        } finally {
            if (forzar) {
                boton.disabled = false;
                boton.innerHTML = '<i class="fas fa-rotate-right me-2"></i> ACTUALIZAR';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const fuente = document.querySelector('input[name="fuente_tipo_cambio"]:checked')?.value || 'MANUAL';
        cambiarFuenteTipoCambio(fuente);

        @if(!empty($bcb))
            document.getElementById('bcb-valor').textContent = 'Bs {{ $bcb['valor_texto'] }} / USD';
            document.getElementById('bcb-fecha').textContent = '{{ $bcb['fecha'] }}';
        @endif
    });
</script>

@endsection
