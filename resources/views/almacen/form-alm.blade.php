@extends('layouts.master')

@section('title', ($movimiento ? 'Editar' : 'Nueva') . ' ' . \Illuminate\Support\Str::title($tipoEtiqueta))

@section('content')
    <div class="main-container w-full">

        {{-- =====================================================
             ENCABEZADO
             ===================================================== --}}

        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
            <div>
                <h1 class="fs-title mb-0 text-black">
                    <i class="fas {{ $esEntrega ? 'fa-arrow-up' : 'fa-arrow-down' }} me-2"></i>
                    {{ $movimiento ? 'EDITAR' : 'NUEVA' }} {{ $tipoEtiqueta }}
                </h1>
                <p class="font-bold small text-black uppercase mb-0">
                    {{ $esEntrega ? 'Salida de mercadería del inventario' : 'Ingreso de mercadería al inventario' }}
                </p>
            </div>

            <a href="{{ route('almacen.index') }}"
               class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> VOLVER
            </a>
        </header>


        {{-- =====================================================
             AVISO DE CONTEXTO
             ===================================================== --}}

        <div class="mb-4 p-3 rounded-3 fw-bold"
             style="background:{{ $esEntrega ? '#fff4e5' : '#e8f5e9' }};border:3px solid #000;">

            <i class="fas fa-circle-info me-1"></i>

            @if($esEntrega)
                La <strong>entrega</strong> descuenta stock. El sistema no permite registrar más
                de lo disponible en el momento de guardar.
            @else
                La <strong>compra</strong> suma stock, genera un lote nuevo y actualiza el último costo.
            @endif
        </div>


        {{-- =====================================================
             FORMULARIO
             ===================================================== --}}

        <form id="formMovimiento"
              onsubmit="return guardarMovimiento(event)"
              class="form-bento">

            @csrf

            <input type="hidden" name="tipo_movimiento" id="movTipo" value="{{ $tipoMovimiento }}">
            <input type="hidden" name="id_movimiento" id="movIdMovimiento" value="{{ $movimiento->id_movimiento ?? '' }}">

            <div class="row g-4 mb-4">

                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label style="font-size:1.3rem;font-weight:900;color:#000;">
                            FECHA <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               name="fecha_movimiento"
                               id="movFecha"
                               required
                               value="{{ old('fecha_movimiento', $movimiento->fecha_movimiento ?? now()->format('Y-m-d')) }}"
                               style="font-size:1.3rem;padding:12px;border:4px solid #000;font-weight:700;">
                    </div>
                </div>

                <div class="col-md-8">
<div class="form-group mb-0">
                            <label style="font-size:1.3rem;font-weight:900;color:#000;">
                                PRODUCTO <span class="text-danger">*</span>
                            </label>

                            {{-- El <select> real se arma desde el catálogo y es
                                 lo que se envía; el buscador de arriba es solo
                                 la forma cómoda de elegirlo. --}}
                            <select name="id_inventario"
                                    id="movIdProducto"
                                    required
                                    style="font-size:1.3rem;">
                                <option value="">SELECCIONE UN PRODUCTO...</option>
                                @foreach($productos as $producto)
                                    <option value="{{ $producto->id_inventario }}"
                                            data-detalle="{{ $producto->codigo_barras ?? $producto->codigo }}"
                                            {{ (string) old('id_inventario', $movimiento->id_inventario ?? '') === (string) $producto->id_inventario ? 'selected' : '' }}>
                                        {{ $producto->nombre_producto }}
                                    </option>
                                @endforeach
                            </select>

                            <small class="text-muted fw-bold">
                                Escriba para filtrar por nombre o por código.
                            </small>

                            <div id="movStockInfo"
                                 class="mt-2 p-2 fw-bold text-center"
                                 style="border:3px solid #000;display:none;background:#f8f9fa;"></div>
                        </div>
                </div>

            </div>


            <div class="row g-4 mb-4">

                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label style="font-size:1.3rem;font-weight:900;color:#000;">
                            CANTIDAD <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                               name="cantidad"
                               id="movCantidad"
                               step="0.01"
                               min="0.01"
                               required
                               placeholder="0.00"
                               value="{{ old('cantidad', $movimiento->cantidad ?? '') }}"
                               oninput="calcularTotal()"
                               style="font-size:1.3rem;padding:12px;border:4px solid #000;font-weight:700;">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label style="font-size:1.3rem;font-weight:900;color:#000;">
                            CÓDIGO DE LOTE
                        </label>
                        <input type="text"
                               name="codigo_lote"
                               id="movCodigoLote"
                               readonly
                               placeholder="LO-000001"
                               value="{{ old('codigo_lote', $movimiento->codigo_lote ?? '') }}"
                               style="font-size:1.3rem;padding:12px;border:4px solid #000;background:#f0f0f0;font-family:monospace;letter-spacing:1px;text-align:center;font-weight:700;">
                        <small class="text-muted fw-bold">
                            @if($movimiento)
                                El lote no se modifica al editar un movimiento ya registrado.
                            @else
                                Se genera automáticamente al guardar.
                            @endif
                        </small>
                    </div>
                </div>

            </div>


            {{-- =================================================
                 BLOQUE DE COMPRA
                 ================================================= --}}

            @unless($esEntrega)

                <div class="row g-4 mb-4">

                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label style="font-size:1.3rem;font-weight:900;color:#000;">
                                PRECIO UNITARIO (Bs)
                            </label>
                            <input type="number"
                                   name="precio_unitario"
                                   id="movPrecioUnitario"
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00"
                                   value="{{ old('precio_unitario', $movimiento->costo_unitario ?? '') }}"
                                   oninput="calcularTotal()"
                                   style="font-size:1.3rem;padding:12px;border:4px solid #000;font-weight:700;">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label style="font-size:1.3rem;font-weight:900;color:#000;">
                                TOTAL (Bs)
                            </label>
                            <input type="text"
                                   id="movTotal"
                                   readonly
                                   placeholder="0.00"
                                   style="font-size:1.3rem;padding:12px;border:4px solid #000;background:#f0f0f0;font-weight:700;">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label style="font-size:1.3rem;font-weight:900;color:#000;">
                                CONDICIÓN DE PAGO <span class="text-danger">*</span>
                            </label>
                            <select name="condicion_pago"
                                    id="movCondicion"
                                    onchange="toggleCondicion()"
                                    style="font-size:1.3rem;">

                                <option value="CONTADO"
                                        {{ old('condicion_pago', $movimiento->condicion_pago ?? 'CONTADO') === 'CONTADO' ? 'selected' : '' }}>
                                    CONTADO
                                </option>

                                <option value="CREDITO"
                                        {{ old('condicion_pago', $movimiento->condicion_pago ?? '') === 'CREDITO' ? 'selected' : '' }}>
                                    CRÉDITO
                                </option>

                            </select>
                        </div>
                    </div>

                </div>


                <div class="row g-4 mb-4" id="bloqueContado">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label style="font-size:1.3rem;font-weight:900;color:#000;">
                                CTA. BANCARIA
                            </label>
                            <select name="id_banco" id="movIdBanco" style="font-size:1.3rem;">
                                <option value="">SELECCIONE CUENTA...</option>
                                @foreach($bancos as $banco)
                                    <option value="{{ $banco->id_banco }}"
                                        {{ (string) old('id_banco', $movimiento->id_banco ?? '') === (string) $banco->id_banco ? 'selected' : '' }}>
                                        {{ $banco->nombre_banco }} - {{ $banco->numero_cuenta }}
                                        ({{ $banco->moneda ?? 'BOB' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label style="font-size:1.3rem;font-weight:900;color:#000;">
                                FECHA DE PAGO
                            </label>
                            <input type="date"
                                   name="fecha_limite_pago"
                                   id="movFechaLimite"
                                   value="{{ old('fecha_limite_pago', $movimiento->fecha_limite_pago ?? '') }}"
                                   style="font-size:1.3rem;padding:12px;border:4px solid #000;font-weight:700;">
                        </div>
                    </div>
                </div>


                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="form-group mb-0">
                            <label style="font-size:1.3rem;font-weight:900;color:#000;">
                                PROVEEDOR <span class="text-danger">*</span>
                            </label>
                            <select name="id_proveedor" id="movIdProveedor" style="font-size:1.3rem;">
                                <option value="">SELECCIONE PROVEEDOR...</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id_proveedor }}"
                                            data-detalle="{{ $proveedor->nit_ci ?? '' }}"
                                            {{ (string) old('id_proveedor', $movimiento->id_proveedor ?? '') === (string) $proveedor->id_proveedor ? 'selected' : '' }}>
                                        {{ $proveedor->nombre_proveedor }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted fw-bold">
                                Escriba para filtrar. Obligatorio siempre; en crédito además define a quién se le paga.
                            </small>
                        </div>
                    </div>
                </div>

            @endunless


            {{-- =================================================
                 BLOQUE DE ENTREGA
                 ================================================= --}}

            @if($esEntrega)

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label style="font-size:1.3rem;font-weight:900;color:#000;">
                                UNIDAD
                            </label>
                            <select name="id_vehiculo"
                                    id="movIdVehiculo"
                                    onchange="autoAsignarConductor()"
                                    style="font-size:1.3rem;">
                                <option value="">SELECCIONE UNIDAD...</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label style="font-size:1.3rem;font-weight:900;color:#000;">
                                CONDUCTOR
                            </label>
                            <select name="id_personal" id="movIdPersonal" style="font-size:1.3rem;">
                                <option value="">SELECCIONE CONDUCTOR...</option>
                            </select>
                            <small class="text-muted fw-bold">
                                Se completa solo al elegir la unidad, si tiene conductor asignado.
                            </small>
                        </div>
                    </div>
                </div>

            @endif


            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="form-group mb-0">
                        <label style="font-size:1.3rem;font-weight:900;color:#000;">
                            OBSERVACIONES
                        </label>
                        <textarea name="observaciones"
                                  id="movObs"
                                  rows="3"
                                  placeholder="DETALLE DEL MOVIMIENTO..."
                                  style="font-size:1.3rem;padding:12px;border:4px solid #000;font-weight:700;">{{ old('observaciones', $movimiento->observaciones ?? '') }}</textarea>
                    </div>
                </div>
            </div>


            {{-- =================================================
                 ACCIONES
                 ================================================= --}}

            <div class="d-flex gap-3 flex-wrap">
                <button type="submit"
                        id="btnGuardarMov"
                        class="btn-bento btn-bento-primary px-5 font-bold flex-grow-1"
                        style="border-width:4px!important;font-size:1.2rem;">
                    <i class="fas fa-save me-2"></i> GUARDAR {{ $tipoEtiqueta }}
                </button>

                <a href="{{ route('almacen.index') }}"
                   class="btn-bento btn-bento-outline font-bold"
                   style="border-width:4px!important;text-decoration:none;font-size:1.2rem;">
                    CANCELAR
                </a>
            </div>

        </form>

    </div>
@endsection


@push('scripts')
    <script>
        const ES_ENTREGA = {{ $esEntrega ? 'true' : 'false' }};
        const TIPO_MOVIMIENTO = '{{ $tipoMovimiento }}';
        const ES_EDICION = {{ $movimiento ? 'true' : 'false' }};

        // Catálogo de productos con su stock actual. Se embebe en la página en vez de
        // pedirlo por fetch: el formulario necesita el stock al pintar y para
        // avisar antes de guardar, y esperar un round-trip solo para eso
        // dejaba la pantalla vacida un instante.
        const productos = {!! Js::from($productos->map(fn ($p) => [
            'id_inventario' => $p->id_inventario,
            'nombre_producto' => $p->nombre_producto,
            'unidad_medida' => $p->unidad_medida,
            'stock_actual' => $p->stock_actual,
            'stock_minimo' => $p->stock_minimo,
        ])->values()) !!};

        let vehiculos = [];
        let personal = [];

        document.addEventListener('DOMContentLoaded', function () {
            cargarVehiculosYPersonal();

            // El buscador reemplaza visualmente a los <select> largos, pero
            // el select sigue siendo el que se envia.
            buscarEnSelect(document.getElementById('movIdProducto'), {
                placeholder: 'ESCRIBA EL NOMBRE O CÓDIGO DEL PRODUCTO...',
                alElegir: mostrarStock
            });

            buscarEnSelect(document.getElementById('movIdProveedor'), {
                placeholder: 'ESCRIBA EL NOMBRE DEL PROVEEDOR...'
            });

            // En la entrega también conviene no scrollear un select de
            // unidades para elegir la que conoce.
            if (ES_ENTREGA) {
                buscarEnSelect(document.getElementById('movIdVehiculo'), {
                    placeholder: 'ESCRIBA LA PLACA DE LA UNIDAD...',
                    alElegir: autoAsignarConductor
                });

                buscarEnSelect(document.getElementById('movIdPersonal'), {
                    placeholder: 'ESCRIBA EL NOMBRE DEL CONDUCTOR...'
                });
            }

            if (ES_ENTREGA) {
                const selVehiculo = document.getElementById('movIdVehiculo');
                const selPersonal = document.getElementById('movIdPersonal');

                if ({{ $movimiento->id_vehiculo ?? 'null' ? 'true' : 'false' }}) {
                    selVehiculo.value = '{{ $movimiento->id_vehiculo ?? '' }}';
                }

                if ({{ $movimiento->id_personal ?? 'null' ? 'true' : 'false' }}) {
                    selPersonal.value = '{{ $movimiento->id_personal ?? '' }}';
                }
            }

            if (ES_EDICION) {
                mostrarStock();
                if (!ES_ENTREGA) {
                    calcularTotal();
                }
            } else if (!ES_ENTREGA) {
                sugerirLote();
            }

            toggleCondicion();
        });


        /* =========================================================
           CARGA DE CATÁLOGOS
           ========================================================= */

        /**
         * Llena unidad y conductor desde la API.
         *
         * El buscador se monta antes de que llegue la respuesta: si la lista
         * llega despues, hay que avisarle para que reconstruya el desplegable
         * con las opciones nuevas.
         */
        function cargarVehiculosYPersonal() {
            // En una COMPRA no hay unidad ni conductor: esos campos solo
            // existen en la entrega. Consultarlos ahi devolvia null y el
            // `innerHTML` reventaba el script entero.
            if (!ES_ENTREGA) {
                return;
            }

            fetch('{{ url('api/vehiculos') }}?estado=1', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;

                    vehiculos = res.data || [];

                    const selVehiculo = document.getElementById('movIdVehiculo');

                    selVehiculo.innerHTML =
                        '<option value="">SELECCIONE UNIDAD...</option>' +
                        vehiculos.map(v =>
                            `<option value="${v.id_vehiculo}">${esc(v.placa_vehiculo)}</option>`
                        ).join('');

                    refrescarBuscador(selVehiculo);
                });

            fetch('{{ url('api/personal') }}', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;

                    personal = res.data || [];

                    const selPersonal = document.getElementById('movIdPersonal');

                    selPersonal.innerHTML =
                        '<option value="">SELECCIONE CONDUCTOR...</option>' +
                        personal.map(p =>
                            `<option value="${p.id_personal}">${esc(p.nombres)} ${esc(p.apellidos)}</option>`
                        ).join('');

                    refrescarBuscador(selPersonal);
                });
        }

        /**
         * Reconstruye el buscador de un <select> cuyas opciones cambiaron.
         *
         * El componente se monta una vez sobre las opciones que hay en ese
         * momento. Si la lista llega por fetch despues, hay que rehacerlo o el
         * desplegable queda vacio para siempre.
         */
        function refrescarBuscador(select) {
            if (!select) return;

            const envoltura = select.closest('.ds-bus');

            if (!envoltura) {
                // Todavia no se habia montado: se monta ahora con la lista llena.
                buscarEnSelect(select, select.id === 'movIdVehiculo'
                    ? { placeholder: 'ESCRIBA LA PLACA DE LA UNIDAD...', alElegir: autoAsignarConductor }
                    : { placeholder: 'ESCRIBA EL NOMBRE DEL CONDUCTOR...' });

                return;
            }

            envoltura.querySelector('.ds-bus__campo')?.dispatchEvent(new Event('focus'));
        }


        /* =========================================================
           STOCK Y CÁLCULOS
           ========================================================= */

        function mostrarStock() {
            const id = document.getElementById('movIdProducto').value;
            const info = document.getElementById('movStockInfo');

            if (!id) {
                info.style.display = 'none';
                return;
            }

            const producto = productos.find(p => p.id_inventario == id);

            if (!producto) {
                info.style.display = 'none';
                return;
            }

            const stock = parseFloat(producto.stock_actual || 0);
            const minimo = parseFloat(producto.stock_minimo || 0);
            const color = stock <= minimo ? '#dc3545' : '#007400';
            const etiqueta = ES_ENTREGA ? 'STOCK DISPONIBLE' : 'STOCK ACTUAL';

            info.innerHTML =
                `<i class="fas fa-box me-2"></i> ${etiqueta}: ` +
                `<span style="color:${color}">${stock.toFixed(2)}</span> ${esc(producto.unidad_medida || '')}`;

            info.style.display = 'block';
        }

        function calcularTotal() {
            const cantidad = parseFloat(document.getElementById('movCantidad').value) || 0;
            const unitario = parseFloat(document.getElementById('movPrecioUnitario').value) || 0;

            document.getElementById('movTotal').value = (cantidad * unitario).toFixed(2);
        }

        function toggleCondicion() {
            const esCredito = document.getElementById('movCondicion').value === 'CREDITO';
            document.getElementById('bloqueContado').style.display = esCredito ? 'none' : 'flex';
        }

        function autoAsignarConductor() {
            const idVehiculo = document.getElementById('movIdVehiculo').value;
            const selPersonal = document.getElementById('movIdPersonal');

            if (!idVehiculo) {
                selPersonal.value = '';
                return;
            }

            const vehiculo = vehiculos.find(v => v.id_vehiculo == idVehiculo);
            selPersonal.value = (vehiculo && vehiculo.id_personal) ? vehiculo.id_personal : '';
        }

        function sugerirLote() {
            fetch('{{ url('api/lotes/ultimo') }}', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(res => {
                    const numero = res.success && res.data
                        ? (parseInt(res.data.codigo_lote.replace('LO-', ''), 10) || 0) + 1
                        : 1;

                    document.getElementById('movCodigoLote').value =
                        'LO-' + String(numero).padStart(6, '0');
                })
                .catch(() => {
                    document.getElementById('movCodigoLote').value = 'LO-000001';
                });
        }


        /* =========================================================
           GUARDADO
           ========================================================= */

        function guardarMovimiento(event) {
            event.preventDefault();

            const btn = document.getElementById('btnGuardarMov');
            const idProducto = document.getElementById('movIdProducto').value;
            const cantidad = parseFloat(document.getElementById('movCantidad').value);

            if (!idProducto) {
                Swal.fire('Requerido', 'Seleccione un producto', 'warning');
                return false;
            }

            if (!cantidad || cantidad <= 0) {
                Swal.fire('Requerido', 'Indique una cantidad mayor a cero', 'warning');
                return false;
            }

            const data = {
                id_inventario: idProducto,
                tipo_movimiento: TIPO_MOVIMIENTO,
                cantidad: cantidad,
                fecha_movimiento: document.getElementById('movFecha').value,
                observaciones: document.getElementById('movObs').value,
            };

            if (!ES_ENTREGA) {
                const proveedor = document.getElementById('movIdProveedor');
                data.condicion_pago = document.getElementById('movCondicion').value;
                data.id_banco = document.getElementById('movIdBanco').value || null;
                data.id_proveedor = proveedor.value || null;
                data.precio_unitario = document.getElementById('movPrecioUnitario').value || null;
                data.precio_compra = document.getElementById('movTotal').value || null;
                data.fecha_limite_pago = document.getElementById('movFechaLimite').value || null;
            }

            if (ES_ENTREGA) {
                data.id_vehiculo = document.getElementById('movIdVehiculo').value || null;
                data.id_personal = document.getElementById('movIdPersonal').value || null;
            }

            if (!ES_ENTREGA && !data.id_proveedor) {
                Swal.fire('Requerido', 'Seleccione el proveedor', 'warning');
                return false;
            }

            if (!ES_ENTREGA && data.condicion_pago === 'CONTADO' && !data.id_banco) {
                Swal.fire('Requerido', 'Seleccione la cuenta bancaria', 'warning');
                return false;
            }

            // Aviso temprano de stock. El backend igual lo valida con
            // lockForUpdate: esto solo evita un viaje de ida y vuelta.
            if (ES_ENTREGA) {
                const producto = productos.find(p => p.id_inventario == idProducto);

                if (producto && cantidad > parseFloat(producto.stock_actual || 0)) {
                    Swal.fire(
                        'Stock Insuficiente',
                        `Solo hay ${parseFloat(producto.stock_actual || 0).toFixed(2)} ` +
                        `${producto.unidad_medida || ''} disponible`,
                        'error'
                    );
                    return false;
                }
            }

            const esEdicion = document.getElementById('movIdMovimiento').value !== '';

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> GUARDANDO...';

            const url = esEdicion
                ? '{{ url('api/almacen/movimientos') }}/' + document.getElementById('movIdMovimiento').value
                : '{{ url('api/almacen/movimientos') }}';

            fetch(url, {
                method: esEdicion ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-2"></i> GUARDAR {{ $tipoEtiqueta }}';

                    if (res.success) {
                        // `data-no-transition` evita el panel de carga: acá se
                        // navega con la sesión intacta y el listado se recarga
                        // desde cero.
                        const destino = document.createElement('a');
                        destino.href = '{{ route('almacen.index') }}';
                        destino.dataset.noTransition = '1';
                        document.body.appendChild(destino);
                        destino.click();

                        Swal.fire({
                            icon: 'success',
                            title: esEdicion ? 'MOVIMIENTO ACTUALIZADO' : 'MOVIMIENTO REGISTRADO',
                            timer: 1200,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', res.message || 'No se pudo guardar el movimiento', 'error');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-2"></i> GUARDAR {{ $tipoEtiqueta }}';
                    Swal.fire('Error de conexión', 'No se pudo comunicar con el servidor', 'error');
                });

            return false;
        }
    </script>
@endpush
