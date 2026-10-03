@extends('layouts.master')

@section('title', 'Gastos Generales')

@push('styles')
<style>
.gastos-table th {
    background: #000 !important;
    color: #fff !important;
    font-weight: 800;
    padding: 12px 10px;
    border: 2px solid #000;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.gastos-table td {
    padding: 10px;
    border: 1px solid #000;
    font-weight: 600;
    vertical-align: middle;
}
.gastos-table tbody tr:hover {
    background: #fff8e1;
}
.filter-card {
    background: #2f2c79;
    border: 4px solid #000;
    padding: 20px;
}
.filter-card label {
    font-weight: 800;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #fff;
}
.filter-card .form-control, .filter-card .form-select {
    border-radius: 0;
    border: 3px solid #000;
    padding: 10px 12px;
    font-weight: 700;
}
</style>
@endpush

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">REGISTRAR GASTOS GENERALES</h1>
            <p class="font-bold small text-black uppercase">
                Clasificación mediante el <a href="{{ route('clasificadores.index') }}" class="text-decoration-underline">Clasificador de Gastos</a>
            </p>
        </div>
        @can('crear', 'gastos_generales')
            <a href="{{ route('gastos-generales.create') }}"
               class="btn-bento btn-bento-primary py-1 px-3 fs-mid font-bold rounded-3 text-decoration-none"
               style="border:3px solid #000;">
                <i class="fas fa-plus me-1"></i> REGISTRAR GASTO
            </a>
        @endcan
    </header>

    <!-- FILTROS -->
    <div class="filter-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label>CLASIFICADOR</label>
                <select class="form-select" id="filterClasificador">
                    <option value="">TODOS</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>FECHA INICIO</label>
                <input type="date" class="form-control" id="filterFechaInicio" value="{{ date('Y-m-01') }}">
            </div>
            <div class="col-md-2">
                <label>FECHA FIN</label>
                <input type="date" class="form-control" id="filterFechaFin" value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <button class="btn fw-bold w-100" style="background:#000;color:#fff;border:3px solid #000;padding:10px;border-radius:0;" onclick="cargarGastos()">
                    <i class="fas fa-search me-1"></i> BUSCAR
                </button>
            </div>
            <div class="col-md-2 text-end">
                <span class="badge bg-black text-warning px-3 py-2 fw-bold fs-6" id="totalGastos">TOTAL: Bs. 0.00</span>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-12">
                <span class="badge px-3 py-2 fw-bold" id="resumenDevoluciones"
                      style="display:none; background:#d4edda;color:#155724;border:2px solid #000;">
                    DEVOLUCIONES: Bs. 0.00
                </span>
            </div>
        </div>
    </div>

    <!-- TABLA -->
    <div id="loadingGastos" class="text-center py-5">
        <div class="spinner-border text-dark" role="status"></div>
        <p class="mt-3 fw-bold">CARGANDO...</p>
    </div>
    <div id="emptyGastos" class="text-center py-5" style="display:none;border:4px solid #000;">
        <i class="fas fa-receipt" style="font-size:64px;opacity:.2;"></i>
        <h3 class="mt-3 fw-bold">NO HAY GASTOS REGISTRADOS</h3>
    </div>
    <div id="tableContainer" style="display:none;">
        <div style="border:4px solid #000;overflow-x:auto;">
            <table class="gastos-table w-100 mb-0">
                <thead>
                    <tr>
                        <th>N° DOC</th>
                        <th>FECHA</th>
                        <th>CLASIFICADOR</th>
                        <th>TIPO</th>
                        <th>CONCEPTO</th>
                        <th>MONTO</th>
                        <th>PAGO</th>
                        <th>BANCO</th>
                        <th>PROVEEDOR</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="gastosBody"></tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    cargarClasificadores();
    cargarGastos();
});

/** Pobla el filtro de clasificadores (alcance general). */
function cargarClasificadores() {
    fetch('{{ url("api/clasificadores") }}?alcance=general', {
        headers: { 'Accept': 'application/json' }
    })
        .then(r => r.json())
        .then(res => {
            const sel = document.getElementById('filterClasificador');
            if (!sel || !res.success) return;

            const grupos = {};
            res.data.forEach(c => {
                (grupos[c.tipo_gasto] = grupos[c.tipo_gasto] || []).push(c);
            });

            let html = '<option value="">TODOS</option>';

            Object.keys(grupos).sort().forEach(tipo => {
                html += `<optgroup label="${esc(tipo)}">`;
                grupos[tipo].forEach(c => {
                    html += `<option value="${c.id_clasificador}">${esc(c.codigo)} — ${esc(c.descripcion)}</option>`;
                });
                html += '</optgroup>';
            });

            sel.innerHTML = html;
            sel.addEventListener('change', cargarGastos);
        });
}

function cargarGastos() {
    const params = new URLSearchParams();

    const clasificador = document.getElementById('filterClasificador').value;
    if (clasificador) params.append('id_clasificador', clasificador);

    params.append('fecha_inicio', document.getElementById('filterFechaInicio').value);
    params.append('fecha_fin', document.getElementById('filterFechaFin').value);

    document.getElementById('loadingGastos').style.display = 'block';
    document.getElementById('tableContainer').style.display = 'none';
    document.getElementById('emptyGastos').style.display = 'none';

    fetch('{{ url("api/gastos-generales") }}?' + params.toString(), {
        headers: { 'Accept': 'application/json' }
    })
        .then(r => r.json())
        .then(res => {
            document.getElementById('loadingGastos').style.display = 'none';

            if (!res.success || !res.data.length) {
                document.getElementById('emptyGastos').style.display = 'block';
                document.getElementById('totalGastos').textContent = 'TOTAL: Bs. 0.00';
                return;
            }

            document.getElementById('tableContainer').style.display = 'block';

            const resumen = res.resumen || { total: 0, total_devoluciones: 0, cantidad: res.data.length };
            document.getElementById('totalGastos').textContent = 'TOTAL: Bs. ' + bs(resumen.total);

            const badge = document.getElementById('resumenDevoluciones');
            if (Math.abs(resumen.total_devoluciones) > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = 'DEVOLUCIONES: Bs. ' + bs(resumen.total_devoluciones);
            } else {
                badge.style.display = 'none';
            }

            renderizar(res.data);
        })
        .catch(() => {
            document.getElementById('loadingGastos').style.display = 'none';
            document.getElementById('emptyGastos').style.display = 'block';
        });
}

/**
 * Render con escape de TODOS los valores de base de datos.
 * `g.concepto`, `g.categoria` y los nombres de banco/proveedor los escribe
 * un usuario; interpolarlos crudos era XSS almacenado.
 */
function renderizar(filas) {
    const cuerpo = document.getElementById('gastosBody');

    cuerpo.innerHTML = filas.map(g => {
        const esDevolucion = !!Number(g.es_devolucion);
        const monto = parseFloat(g.monto || 0);

        const colorMonto = esDevolucion ? '#007400' : '#dc3545';

        const badgeClasificador = g.clasificador_codigo
            ? `<span class="badge" style="background:#000;color:#fff;border:2px solid #000;font-family:monospace;">${esc(g.clasificador_codigo)}</span>`
            : '<span class="badge scope-no">LEGACY</span>';

        const marcaDevolucion = esDevolucion
            ? `<span class="badge" style="background:#d4edda;color:#155724;border:2px solid #000;font-weight:800;">DEVOLUCION</span>`
            : '';

        @php
// Blade NO parsea bien los parentesis anidados dentro de @json(): corta en el
// primer ")". Por eso los permisos se calculan aqui y se pasan como variables
// simples. Ademas @can dentro de un literal JS es fragil.
$puedeEditarGasto  = auth()->user()?->can('editar', 'gastos_generales') ?? false;
$puedeAnularGasto  = auth()->user()?->can('eliminar', 'gastos_generales') ?? false;
@endphp

// Los permisos llegan como flags desde Blade.
        const puedeEditar = @json($puedeEditarGasto);
        const puedeAnular = @json($puedeAnularGasto);

        let acciones = '';

        if (puedeEditar) {
            acciones += `<a href="{{ url("gastos-generales") }}/${Number(g.id_gasto_general)}/editar"
                         class="btn btn-sm" style="background:#ffc107;border:2px solid #000;" title="EDITAR">
                            <i class="fas fa-edit"></i>
                        </a>`;
        }

        if (puedeAnular) {
            acciones += `<button class="btn btn-sm" style="background:#dc3545;border:2px solid #000;color:#fff;"
                                title="ANULAR" onclick="anularGasto(${Number(g.id_gasto_general)})">
                            <i class="fas fa-trash"></i>
                        </button>`;
        }

        return `<tr>
            <td class="fw-bold font-monospace" style="white-space:nowrap;">${esc(g.nro_documento || '—')}</td>
            <td style="white-space:nowrap;">${esc(g.fecha_gasto || '—')}</td>
            <td style="text-align:center;">${badgeClasificador}</td>
            <td style="text-align:center;">
                <span class="badge" style="background:#2f2c79;color:#fff;border:2px solid #000;">${esc(g.categoria || '—')}</span>
            </td>
            <td class="fw-bold">
                ${esc(g.concepto || '—')}
                ${marcaDevolucion ? '<br>' + marcaDevolucion : ''}
            </td>
            <td class="fw-bold" style="color:${colorMonto};">Bs. ${bs(monto)}</td>
            <td style="text-align:center;">${esc(g.condicion_pago || '—')}</td>
            <td>${txt(g.nombre_banco)}</td>
            <td>${txt(g.nombre_proveedor)}</td>
            <td style="text-align:center;white-space:nowrap;">${acciones}</td>
        </tr>`;
    }).join('');
}

function anularGasto(id) {
    Swal.fire({
        title: '¿ANULAR ESTE GASTO GENERAL?',
        html: `<div class="text-start">
            <p class="mb-0 small">
                El gasto no se borra: queda registrado con estado <strong>ANULADO</strong> y sale de los totales.
                Esto conserva el historial contable.
            </p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'SÍ, ANULAR',
        cancelButtonText: 'CANCELAR'
    }).then((resultado) => {
        if (!resultado.isConfirmed) return;

        fetch('{{ url("api/gastos-generales") }}/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire('ANULADO', esc(res.message || 'Gasto general anulado'), 'success').then(cargarGastos);
                } else {
                    Swal.fire('ERROR', esc(res.message || 'No se pudo anular'), 'error');
                }
            });
    });
}
</script>
@endpush
@endsection
