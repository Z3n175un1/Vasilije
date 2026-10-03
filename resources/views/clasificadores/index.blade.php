@extends('layouts.master')

@section('title', 'Clasificador de Gastos')

@push('styles')
<style>
/* =========================================================
   TARJETA DE TIPO
   El catálogo se agrupa por tipo: con 22 filas en una sola
   tabla el operador tiene que escanearla entera para
   encontrar un código. Agrupada, cada bloque responde a
   "combustible, mantenimiento, viáticos..." de un vistazo.
   ========================================================= */
.clas-tipo {
    border: 4px solid #000;
    background: #fff;
    margin-bottom: 1.25rem;
    overflow: hidden;
}

.clas-tipo__cabecera {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: 1rem 1.25rem;
    background: #2f2c79;
    color: #fff;
    border: none;
    cursor: pointer;
    text-align: left;
}

.clas-tipo__cabecera:hover,
.clas-tipo__cabecera:focus-visible {
    background: #403c91;
}

.clas-tipo__nombre {
    flex: 1;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.95rem;
}

.clas-tipo__conteo {
    background: #fff;
    color: #2f2c79;
    border: 2px solid #000;
    border-radius: 999px;
    padding: 0.15rem 0.75rem;
    font-weight: 800;
    font-size: 0.8rem;
    white-space: nowrap;
}

.clas-tipo__flecha {
    transition: transform .2s ease;
}

.clas-tipo[data-abierto='1'] .clas-tipo__flecha {
    transform: rotate(180deg);
}

.clas-tipo__cuerpo {
    max-height: 0;
    overflow: hidden;
    transition: max-height .3s ease;
}

/* =========================================================
   TABLA
   ========================================================= */
.clas-table th {
    background: #eef2ff;
    color: #000;
    font-weight: 800;
    padding: 10px;
    border-bottom: 3px solid #000;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    white-space: nowrap;
}

.clas-table td {
    padding: 10px;
    border-bottom: 1px solid #d4d4d4;
    font-weight: 600;
    vertical-align: middle;
}

.clas-table tbody tr:last-child td {
    border-bottom: none;
}

.clas-table tbody tr:hover {
    background: #fff8e1;
}

.clas-codigo {
    background: #000;
    color: #fff;
    padding: 0.25rem 0.5rem;
    font-family: ui-monospace, monospace;
    font-weight: 800;
    font-size: 0.8rem;
    white-space: nowrap;
}

/* =========================================================
   INDICADORES DE ALCANCE
   ========================================================= */
.scope-si {
    background: #d4edda;
    color: #155724;
    border: 2px solid #000;
    font-weight: 800;
    padding: 3px 10px;
    display: inline-block;
    font-size: 0.75rem;
}

.scope-no {
    background: #f1f1f1;
    color: #767676;
    border: 2px dashed #b8b8b8;
    font-weight: 700;
    padding: 3px 10px;
    display: inline-block;
    font-size: 0.75rem;
}

/* =========================================================
   ACCIONES
   Los botones llevan texto, no solo icono: un cuadrado de
   32px con un glifo no dice qué hace, y en una tabla con
   22 filas hay que escanear la columna entera.
   ========================================================= */
.clas-accion {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border: 3px solid #000;
    padding: 0.35rem 0.7rem;
    font-weight: 800;
    font-size: 0.78rem;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    background: #fff;
    color: #000;
}

.clas-accion:hover,
.clas-accion:focus-visible {
    filter: brightness(0.92);
}

.clas-accion--editar {
    background: #ffc107;
}

.clas-accion--quitar {
    background: #dc3545;
    color: #fff;
}

.clas-accion--agregar {
    background: #007400;
    color: #fff;
}

/* =========================================================
   INDICADORES Y FILTROS
   ========================================================= */
.kpi {
    border: 4px solid #000;
    padding: 1rem;
    text-align: center;
}

.filter-card label {
    font-weight: 800;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #000;
    margin-bottom: 0.35rem;
}

.filter-card input,
.filter-card select {
    border: 3px solid #000;
    border-radius: 0;
    font-weight: 700;
}
</style>
@endpush

@section('content')
<div class="main-container w-full">

    {{-- =====================================================
         ENCABEZADO
         ===================================================== --}}

    <header class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div>
            <h1 class="fs-title mb-0 text-black">CLASIFICADOR DE GASTOS</h1>
            <p class="font-bold small text-black uppercase mb-0">
                Catálogo maestro · Código · Descripción · Tipo · Alcance
            </p>
        </div>

        @can('administrar', 'clasificadores')
            <a href="{{ route('clasificadores.create') }}"
               class="btn-bento btn-bento-primary py-2 px-3 font-bold rounded-3 text-decoration-none d-inline-flex align-items-center gap-2"
               style="border:3px solid #000;font-size:1rem;">
                <i class="fas fa-plus"></i> NUEVO CLASIFICADOR
            </a>
        @endcan
    </header>


    {{-- =====================================================
         INDICADORES
         ===================================================== --}}

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="kpi" style="background:#e2e3e5;">
                <div class="small fw-bold text-uppercase text-secondary">TOTAL</div>
                <div class="fs-3 fw-bold" id="kpiTotal">{{ $resumen['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi" style="background:#e2ffd6;">
                <div class="small fw-bold text-uppercase text-secondary">AFECTA UNIDAD</div>
                <div class="fs-3 fw-bold" style="color:#007400;" id="kpiUnidad">{{ $resumen['unidad'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi" style="background:#fff3cd;">
                <div class="small fw-bold text-uppercase text-secondary">AFECTA GENERAL</div>
                <div class="fs-3 fw-bold" style="color:#746700;" id="kpiGeneral">{{ $resumen['general'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi" style="background:#ffdcd6;">
                <div class="small fw-bold text-uppercase text-secondary">AMBOS</div>
                <div class="fs-3 fw-bold" style="color:#740000;" id="kpiAmbos">{{ $resumen['ambos'] }}</div>
            </div>
        </div>
    </div>


    {{-- =====================================================
         FILTROS
         ===================================================== --}}

    <div class="filter-card mb-4 bg-white p-3" style="border:4px solid #000;">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label for="filtroBusqueda">BÚSQUEDA</label>
                <input type="search" class="form-control" id="filtroBusqueda"
                       placeholder="CÓDIGO, DESCRIPCIÓN O TIPO...">
            </div>

            <div class="col-6 col-lg-3">
                <label for="filtroTipo">TIPO DE GASTO</label>
                <select class="form-select" id="filtroTipo">
                    <option value="">TODOS LOS TIPOS</option>
                    @foreach($tipos as $tipo)
                        <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-lg-2">
                <label for="filtroAlcance">ALCANCE</label>
                <select class="form-select" id="filtroAlcance">
                    <option value="">TODOS</option>
                    <option value="unidad">SOLO UNIDAD</option>
                    <option value="general">SOLO GENERAL</option>
                    <option value="ambos">AMBOS</option>
                    <option value="ninguno">NINGUNO</option>
                </select>
            </div>

            <div class="col-12 col-lg-2 d-flex gap-2">
                <button type="button" class="btn fw-bold flex-grow-1" id="btnFiltrar"
                        style="background:#2f2c79;color:#fff;border:3px solid #000;padding:10px;border-radius:0;">
                    <i class="fas fa-filter me-1"></i> FILTRAR
                </button>

                <button type="button" class="btn fw-bold" id="btnLimpiar"
                        title="QUITAR FILTROS" aria-label="Quitar filtros"
                        style="background:#fff;color:#000;border:3px solid #000;padding:10px 14px;border-radius:0;">
                    <i class="fas fa-rotate-left"></i>
                </button>
            </div>
        </div>

        <div class="small fw-bold mt-2" style="color:#2f2c79;">
            Mostrando <span id="contadorVisibles">{{ count($clasificadores) }}</span>
            de {{ count($clasificadores) }} clasificadores
        </div>
    </div>


    {{-- =====================================================
         LISTA AGRUPADA POR TIPO
         ===================================================== --}}

    <div id="tablaVacia" class="text-center py-5" style="display:none; border:4px solid #000;">
        <i class="fas fa-magnifying-glass" style="font-size:56px; opacity:.2;"></i>
        <h3 class="mt-3 fw-bold">NO HAY CLASIFICADORES QUE COINCIDAN</h3>
        <p class="fw-bold mb-0" style="color:#666;">Pruebe con otro criterio de búsqueda.</p>
    </div>

    <div id="listaClasificadores">
        @forelse($clasificadoresAgrupados as $tipo => $items)

            @php
                $abierto = $loop->first || count($items) <= 4;
            @endphp

            <section class="clas-tipo" data-abierto="{{ $abierto ? '1' : '0' }}">
                <button type="button" class="clas-tipo__cabecera" aria-expanded="{{ $abierto ? 'true' : 'false' }}">

                    <i class="fas fa-chevron-down clas-tipo__flecha" aria-hidden="true"></i>

                    <span class="clas-tipo__nombre">{{ $items[0]->tipo_gasto }}</span>

                    <span class="clas-tipo__conteo">{{ count($items) }}</span>
                </button>

                <div class="clas-tipo__cuerpo" style="{{ $abierto ? 'max-height:3000px;' : '' }}">
                    <div style="overflow-x:auto;">
                        <table class="clas-table w-100 mb-0">
                            <thead>
                                <tr>
                                    <th style="width:120px;">CÓDIGO ID</th>
                                    <th>DESCRIPCIÓN</th>
                                    <th style="width:120px;">AFECTA UNIDAD</th>
                                    <th style="width:140px;">AFECTA GENERAL</th>
                                    <th style="width:120px;">ESTADO</th>
                                    @can('administrar', 'clasificadores')
                                        <th style="width:230px;">ACCIONES</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $c)
                                    <tr data-codigo="{{ $c->codigo }}"
                                        data-descripcion="{{ $c->descripcion }}"
                                        data-tipo="{{ $c->tipo_gasto }}"
                                        data-unidad="{{ $c->afecta_unidad ? '1' : '0' }}"
                                        data-general="{{ $c->afecta_general ? '1' : '0' }}">
                                        <td>
                                            <span class="clas-codigo">{{ $c->codigo }}</span>
                                        </td>
                                        <td class="fw-bold">{{ $c->descripcion }}</td>
                                        <td class="text-center">
                                            <span class="{{ $c->afecta_unidad ? 'scope-si' : 'scope-no' }}">
                                                {{ $c->afecta_unidad ? 'SÍ' : 'NO' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="{{ $c->afecta_general ? 'scope-si' : 'scope-no' }}">
                                                {{ $c->afecta_general ? 'SÍ' : 'NO' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($c->estado === 'ACTIVO')
                                                <span class="scope-si">ACTIVO</span>
                                            @else
                                                <span class="scope-no">INACTIVO</span>
                                            @endif
                                        </td>

                                        @can('administrar', 'clasificadores')
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route('clasificadores.edit', $c->id_clasificador) }}"
                                                       class="clas-accion clas-accion--editar">
                                                        <i class="fas fa-pen" aria-hidden="true"></i> EDITAR
                                                    </a>

                                                    <button type="button"
                                                            class="clas-accion clas-accion--quitar"
                                                            onclick="eliminarClasificador({{ $c->id_clasificador }}, @js($c->codigo))">
                                                        <i class="fas fa-trash" aria-hidden="true"></i> QUITAR
                                                    </button>
                                                </div>
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        @empty
            <div class="text-center py-5" style="border:4px solid #000;">
                <i class="fas fa-layer-group" style="font-size:56px; opacity:.2;"></i>
                <h3 class="mt-3 fw-bold">EL CATÁLOGO ESTÁ VACÍO</h3>
            </div>
        @endforelse
    </div>


    {{-- =====================================================
         NOTA DE ALCANCE
         ===================================================== --}}

    <div class="mt-4 p-3" style="background:#eef2ff;border:3px solid #2f2c79;">
        <div class="fw-bold text-uppercase mb-1" style="color:#2f2c79;">
            <i class="fas fa-circle-info me-1"></i> CÓMO FUNCIONA EL ALCANCE
        </div>
        <div class="small" style="line-height:1.6;">
            Al marcar <strong>AFECTA UNIDAD</strong>, el clasificador aparece en el formulario de
            <strong>GASTOS DE UNIDAD</strong>.
            Al marcar <strong>AFECTA GENERAL</strong>, aparece en
            <strong>GASTOS GENERALES</strong>.
            Puede tener ambos alcances. Un clasificador marcado en ninguno queda fuera de ambos formularios.
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    ['filtroBusqueda', 'filtroTipo', 'filtroAlcance'].forEach(function (id) {
        document.getElementById(id)?.addEventListener('input', aplicarFiltros);
        document.getElementById(id)?.addEventListener('change', aplicarFiltros);
    });

    document.getElementById('btnFiltrar')?.addEventListener('click', aplicarFiltros);

    document.getElementById('btnLimpiar')?.addEventListener('click', function () {
        document.getElementById('filtroBusqueda').value = '';
        document.getElementById('filtroTipo').value = '';
        document.getElementById('filtroAlcance').value = '';

        aplicarFiltros();
    });

    // Desplegar / replegar cada bloque de tipo.
    document.querySelectorAll('.clas-tipo__cabecera').forEach(function (cabecera) {
        cabecera.addEventListener('click', function () {
            const bloque = cabecera.closest('.clas-tipo');
            const abierto = bloque.dataset.abierto === '1';

            bloque.dataset.abierto = abierto ? '0' : '1';
            cabecera.setAttribute('aria-expanded', abierto ? 'false' : 'true');

            bloque.querySelector('.clas-tipo__cuerpo').style.maxHeight =
                abierto ? '0px' : bloque.scrollHeight + 'px';
        });
    });

    if (document.querySelectorAll('[data-codigo]').length === 0) {
        document.getElementById('tablaVacia').style.display = 'block';
        document.getElementById('listaClasificadores').style.display = 'none';
    }
});

/**
 * Filtra en cliente sobre el HTML ya renderizado.
 *
 * Compara contra el dataset del <tr>, no contra el texto mostrado, para que
 * acentos y mayúsculas no generen falsos negativos.
 */
function aplicarFiltros() {
    const busqueda = (document.getElementById('filtroBusqueda').value || '').toLowerCase().trim();
    const tipo = document.getElementById('filtroTipo').value;
    const alcance = document.getElementById('filtroAlcance').value;

    let visibles = 0;

    document.querySelectorAll('[data-codigo]').forEach(function (tr) {
        const codigo = (tr.dataset.codigo || '').toLowerCase();
        const descripcion = (tr.dataset.descripcion || '').toLowerCase();
        const tipoFila = tr.dataset.tipo || '';
        const unidad = tr.dataset.unidad === '1';
        const general = tr.dataset.general === '1';

        let pasa = true;

        if (busqueda) {
            pasa = codigo.includes(busqueda)
                || descripcion.includes(busqueda)
                || tipoFila.toLowerCase().includes(busqueda);
        }

        if (pasa && tipo) {
            pasa = tipoFila === tipo;
        }

        if (pasa && alcance) {
            if (alcance === 'unidad') pasa = unidad;
            else if (alcance === 'general') pasa = general;
            else if (alcance === 'ambos') pasa = unidad && general;
            else if (alcance === 'ninguno') pasa = !unidad && !general;
        }

        tr.style.display = pasa ? '' : 'none';
        if (pasa) visibles++;
    });

    // Un bloque de tipo se oculta completo si no le queda ninguna fila, para
    // no dejar cabeceras sueltas de grupos vacíos.
    document.querySelectorAll('.clas-tipo').forEach(function (bloque) {
        const filasVisibles = bloque.querySelectorAll('[data-codigo]:not([style*="display: none"])').length;

        bloque.style.display = filasVisibles === 0 ? 'none' : '';

        if (filasVisibles > 0 && bloque.dataset.abierto === '1') {
            bloque.querySelector('.clas-tipo__cuerpo').style.maxHeight = bloque.scrollHeight + 'px';
        }
    });

    const hayResultados = visibles > 0;

    document.getElementById('tablaVacia').style.display = hayResultados ? 'none' : 'block';
    document.getElementById('listaClasificadores').style.display = hayResultados ? '' : 'none';

    const contador = document.getElementById('contadorVisibles');
    if (contador) {
        contador.textContent = visibles;
    }
}

function eliminarClasificador(id, codigo) {
    Swal.fire({
        title: 'QUITAR CLASIFICADOR',
        html: `<div class="text-start">
            <p class="fw-bold mb-2">Código: <span class="text-danger">${esc(codigo)}</span></p>
            <p class="mb-0 small">
                Si el clasificador ya tiene gastos registrados se desactivará en lugar de eliminarse,
                para conservar el historial contable.
            </p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'SÍ, QUITAR',
        cancelButtonText: 'CANCELAR',
        confirmButtonColor: '#dc3545',
    }).then(function (resultado) {
        if (!resultado.isConfirmed) return;

        fetch('{{ url("clasificadores") }}/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
            .then(r => r.json())
            .then(function (res) {
                if (res.success) {
                    Swal.fire('LISTO', esc(res.message), 'success').then(() => window.location.reload());
                } else {
                    Swal.fire('ERROR', esc(res.message || 'No se pudo quitar'), 'error');
                }
            })
            .catch(() => Swal.fire('ERROR', 'Error de comunicación', 'error'));
    });
}
</script>
@endpush