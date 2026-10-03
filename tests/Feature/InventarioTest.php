<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * INVENTARIO TRANSACCIONAL
 *
 * Lo que se corrige con estos tests es el fallo mas caro del sistema: el
 * stock se movia FUERA de cualquier transaccion.
 *
 * El codigo anterior, al editar un movimiento, hacia:
 *   1. revertir el delta viejo del stock
 *   2. revertir el lote
 *   3. aplicar el delta nuevo
 *   4. actualizar el lote
 * Si fallaba el paso 3, el paso 1 ya estaba confirmado: el inventario
 * quedaba corrupto de forma permanente y silenciosa, sin rastro.
 *
 * InventarioService ejecuta los cuatro pasos dentro de la misma
 * transaccion, con bloqueo de fila (lockForUpdate) sobre el producto.
 *
 * @uses \App\Services\InventarioService
 * @uses \App\Services\DocumentoService
 */
class InventarioTest extends TestCase
{
    use RefreshDatabase;

    private function stockDe(int $idProducto): float
    {
        return (float) DB::table('global.inventario')->where('id_inventario', $idProducto)->value('stock_actual');
    }

    /** @return array<string, mixed> */
    private function compra(int $idProducto, float $cantidad, array $extra = []): array
    {
        return array_merge([
            'id_inventario' => $idProducto,
            'tipo_movimiento' => 'COMPRA',
            'cantidad' => $cantidad,
            'fecha_movimiento' => now()->format('Y-m-d'),
            'precio_unitario' => 10.00,
            'precio_compra' => $cantidad * 10,
            'codigo_lote' => 'LO-000001',
        ], $extra);
    }

    /** @return array<string, mixed> */
    private function salida(int $idProducto, float $cantidad, array $extra = []): array
    {
        return array_merge([
            'id_inventario' => $idProducto,
            'tipo_movimiento' => 'SALIDA',
            'cantidad' => $cantidad,
            'fecha_movimiento' => now()->format('Y-m-d'),
        ], $extra);
    }

    // =================================================================
    // COLUMNA GENERADA
    // =================================================================

    #[Test]
    public function costo_total_lo_calcula_postgres_y_no_lo_manda_el_servicio(): void
    {
        // `costo_total` es GENERATED ALWAYS AS (cantidad * costo_unitario).
        // Incluirla en el INSERT hace fallar la operacion con
        // "no se puede insertar un valor no-predeterminado en una columna
        // generada", y con ella caia todo registro de compra.
        $columna = DB::selectOne("
            SELECT generation_expression
            FROM information_schema.columns
            WHERE table_schema = 'global'
              AND table_name = 'movimientos_inventario'
              AND column_name = 'costo_total'
        ");

        $this->assertNotNull($columna?->generation_expression, 'costo_total debe seguir siendo una columna generada.');
        $this->assertStringContainsString('cantidad', $columna->generation_expression);
        $this->assertStringContainsString('costo_unitario', $columna->generation_expression);

        $producto = $this->producto(10);

        $respuesta = $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 4, ['precio_unitario' => 25.5]));

        $respuesta->assertOk()->assertJsonPath('success', true);

        // El total lofilled PostgreSQL, no la aplicacion.
        $movimiento = DB::table('global.movimientos_inventario')
            ->where('id_inventario', $producto)
            ->orderByDesc('id_movimiento')
            ->first();

        $this->assertEqualsWithDelta(102.0, (float) $movimiento->costo_total, 0.01);
    }

    #[Test]
    public function editar_un_movimiento_tambien_respeta_la_columna_generada(): void
    {
        $producto = $this->producto(10);

        $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 3, ['precio_unitario' => 10.0]))
            ->assertOk();

        $movimiento = DB::table('global.movimientos_inventario')
            ->where('id_inventario', $producto)
            ->orderByDesc('id_movimiento')
            ->first();

        $this->actingAs($this->usuarioConRol('operador'))
            ->putJson(
                '/api/almacen/movimientos/' . $movimiento->id_movimiento,
                $this->compra($producto, 7, ['precio_unitario' => 12.0])
            )
            ->assertOk();

        $actualizado = DB::table('global.movimientos_inventario')
            ->where('id_movimiento', $movimiento->id_movimiento)
            ->first();

        $this->assertEqualsWithDelta(84.0, (float) $actualizado->costo_total, 0.01);
    }

    // =================================================================
    // STOCK
    // =================================================================

    #[Test]
    public function una_compra_suma_stock(): void
    {
        $producto = $this->producto(10);

        $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 5))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(15.0, $this->stockDe($producto));
    }

    #[Test]
    public function una_salida_resta_stock(): void
    {
        $producto = $this->producto(10);

        $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->salida($producto, 4))
            ->assertOk();

        $this->assertEquals(6.0, $this->stockDe($producto));
    }

    #[Test]
    public function no_se_permite_salir_mas_de_lo_disponible(): void
    {
        $producto = $this->producto(10);

        $respuesta = $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->salida($producto, 25));

        // 422 y no 500: es un error de negocio, no una excepcion.
        $respuesta->assertStatus(422)->assertJsonPath('success', false);
        $this->assertStringContainsString('insuficiente', mb_strtolower($respuesta->json('message')));

        // El stock no debe haberse tocado.
        $this->assertEquals(10.0, $this->stockDe($producto));
        $this->assertDatabaseCount('global.movimientos_inventario', 0);
    }

    #[Test]
    public function el_stock_nunca_queda_negativo(): void
    {
        $producto = $this->producto(3);

        $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->salida($producto, 10))
            ->assertStatus(422);

        $this->assertGreaterThanOrEqual(0.0, $this->stockDe($producto));
    }

    #[Test]
    public function una_cantidad_cero_o_negativa_se_rechaza_en_validacion(): void
    {
        $producto = $this->producto(10);

        $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 0))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cantidad');

        $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->compra($producto, -5))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cantidad');
    }

    // =================================================================
    // TRANSACCIONALIDAD AL EDITAR
    // =================================================================

    #[Test]
    public function editar_un_movimiento_revierte_el_delta_anterior(): void
    {
        $producto = $this->producto(10);
        $usuario = $this->usuarioConRol('operador');

        $id = $this->actingAs($usuario)
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 5))
            ->json('id_movimiento');

        $this->assertEquals(15.0, $this->stockDe($producto));

        // 5 -> 8. El delta viejo (5) se revierte y entra el nuevo (8).
        $this->actingAs($usuario)
            ->putJson('/api/almacen/movimientos/' . $id, $this->compra($producto, 8))
            ->assertOk();

        $this->assertEquals(18.0, $this->stockDe($producto), '10 + 8 = 18, no 10 + 5 + 8.');
    }

    #[Test]
    public function cambiar_compra_por_salida_deja_el_stock_correcto(): void
    {
        $producto = $this->producto(10);
        $usuario = $this->usuarioConRol('operador');

        $id = $this->actingAs($usuario)
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 5))
            ->json('id_movimiento');

        $this->assertEquals(15.0, $this->stockDe($producto));

        // Se convierte la compra en una salida de 3.
        //   10 inicial
        // + 5 (compra)        = 15
        // - 5 (se revierte)   = 10
        // - 3 (salida nueva)  =  7
        $this->actingAs($usuario)
            ->putJson('/api/almacen/movimientos/' . $id, $this->salida($producto, 3))
            ->assertOk();

        $this->assertEquals(7.0, $this->stockDe($producto));
    }

    #[Test]
    public function una_edicion_que_falla_no_deja_el_stock_corrupto(): void
    {
        $producto = $this->producto(10);
        $usuario = $this->usuarioConRol('operador');

        $id = $this->actingAs($usuario)
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 5))
            ->json('id_movimiento');

        $stockAntes = $this->stockDe($producto);
        $this->assertEquals(15.0, $stockAntes);

        // Se intenta convertir en una salida que excede el stock disponible.
        // El servicio debe revertir COMPRA, detectar el faltante y ABORTAR la
        // transaccion completa: el stock debe volver a 15, no quedar en 10.
        $respuesta = $this->actingAs($usuario)
            ->putJson('/api/almacen/movimientos/' . $id, $this->salida($producto, 999));

        $respuesta->assertStatus(422)->assertJsonPath('success', false);

        $this->assertEquals(
            $stockAntes,
            $this->stockDe($producto),
            'Una edicion fallida NO debe dejar el stock modificado: todo va en la misma transaccion.'
        );

        // Y el movimiento sigue siendo la compra original.
        $movimiento = DB::table('global.movimientos_inventario')->where('id_movimiento', $id)->first();
        $this->assertSame('COMPRA', $movimiento->tipo_movimiento);
        $this->assertEquals(5.0, (float) $movimiento->cantidad);
    }

    #[Test]
    public function eliminar_un_movimiento_revierte_el_stock(): void
    {
        $producto = $this->producto(10);
        $usuario = $this->usuarioConRol('supervisor');

        $id = $this->actingAs($usuario)
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 6))
            ->json('id_movimiento');

        $this->assertEquals(16.0, $this->stockDe($producto));

        $this->actingAs($usuario)
            ->deleteJson('/api/almacen/movimientos/' . $id)
            ->assertOk();

        $this->assertEquals(10.0, $this->stockDe($producto));
        $this->assertDatabaseMissing('global.movimientos_inventario', ['id_movimiento' => $id]);
    }

    // =================================================================
    // LOTES
    // =================================================================

    #[Test]
    public function una_compra_crea_el_lote_con_su_cantidad(): void
    {
        $producto = $this->producto(0);

        $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 20))
            ->assertOk();

        $lote = DB::table('global.lotes')->where('codigo_lote', 'LO-000001')->first();

        $this->assertNotNull($lote);
        $this->assertEquals(20.0, (float) $lote->cantidad_actual);
        $this->assertEquals(20.0, (float) $lote->cantidad_inicial);
        $this->assertEquals($producto, (int) $lote->id_inventario);
    }

    #[Test]
    public function dos_compras_del_mismo_lote_acumulan_en_un_solo_lote(): void
    {
        $producto = $this->producto(0);
        $usuario = $this->usuarioConRol('operador');

        $this->actingAs($usuario)->postJson('/api/almacen/movimientos', $this->compra($producto, 10));
        $this->actingAs($usuario)->postJson('/api/almacen/movimientos', $this->compra($producto, 5));

        $lotes = DB::table('global.lotes')->where('id_inventario', $producto)->get();

        $this->assertCount(1, $lotes, 'El mismo codigo de lote debe consolidar en una fila.');
        $this->assertEquals(15.0, (float) $lotes->first()->cantidad_actual);
    }

    #[Test]
    public function el_movimiento_queda_enlazado_a_su_lote(): void
    {
        $producto = $this->producto(0);

        $id = $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 10))
            ->json('id_movimiento');

        $movimiento = DB::table('global.movimientos_inventario')->where('id_movimiento', $id)->first();

        $this->assertNotNull($movimiento->id_lote, 'El movimiento debe apuntar al lote que creo.');
    }

    #[Test]
    public function el_costo_total_se_calcula_siempre(): void
    {
        $producto = $this->producto(0);

        // La columna `costo_total` existe en produccion y la lee el estado de
        // cuenta, pero el codigo no la escribia nunca.
        $id = $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 12, ['precio_unitario' => 7.50]))
            ->json('id_movimiento');

        $movimiento = DB::table('global.movimientos_inventario')->where('id_movimiento', $id)->first();

        $this->assertEquals(7.50, (float) $movimiento->costo_unitario);
        $this->assertEquals(90.00, (float) $movimiento->costo_total, '12 x 7.50 = 90.00');
    }

    #[Test]
    public function la_compra_actualiza_el_ultimo_costo_del_producto(): void
    {
        $producto = $this->producto(0, ['precio_compra' => 5, 'ultimo_costo' => 5]);

        $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 4, ['precio_unitario' => 22.00]));

        $fila = DB::table('global.inventario')->where('id_inventario', $producto)->first();

        $this->assertEquals(22.00, (float) $fila->precio_compra);
        $this->assertEquals(22.00, (float) $fila->ultimo_costo);
    }

    // =================================================================
    // CODES DE PRODUCTO
    // =================================================================

    #[Test]
    public function el_siguiente_codigo_de_lote_es_consecutivo(): void
    {
        $servicio = app(\App\Services\InventarioService::class);

        $primero = $servicio->siguienteCodigoLote();
        $this->assertMatchesRegularExpression('/^LO-\d{6}$/', $primero);

        $producto = $this->producto(0);
        $this->actingAs($this->usuarioConRol('operador'))
            ->postJson('/api/almacen/movimientos', $this->compra($producto, 1, ['codigo_lote' => $primero]));

        $this->assertNotSame($primero, $servicio->siguienteCodigoLote());
    }

    #[Test]
    public function el_codigo_de_producto_se_genera_a_partir_del_grupo(): void
    {
        $categoriaId = DB::table('global.categorias_almacen')->insertGetId([
            'nombre' => 'Lubricantes',
            'created_at' => now(),
        ], 'id_categoria');

        $this->actingAs($this->usuarioConRol('operador'))
            ->post('/items', [
                'nombre_producto' => 'Aceite hidraulico',
                'id_categoria' => $categoriaId,
                'unidad_medida' => 'GALON',
            ])
            ->assertSessionHasNoErrors();

        $codigo = DB::table('global.inventario')->where('nombre_producto', 'Aceite hidraulico')->value('codigo');

        $this->assertMatchesRegularExpression('/^LU-\d{3}-\d{5}$/', $codigo);
    }

    // =================================================================
    // NUMERACION ATOMICA DE DOCUMENTOS
    // =================================================================

    #[Test]
    public function la_numeracion_de_gastos_no_se_repite(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = $this->vehiculo();
        $banco = $this->banco();
        $clasificador = $this->clasificadorDeUnidad('Peaje');

        $numeros = [];

        for ($i = 0; $i < 12; $i++) {
            $this->actingAs($admin)->post('/gastos', [
                'id_vehiculo' => $vehiculo,
                'id_clasificador' => $clasificador,
                'monto' => 10 + $i,
                'fecha_gasto' => now()->format('Y-m-d'),
                'concepto' => 'Gasto ' . $i,
                'condicion_pago' => 'CONTADO',
                'id_banco' => $banco,
            ])->assertSessionHasNoErrors();

            $numeros[] = DB::table('global.gastos')->latest('id_gasto')->value('nro_documento');
        }

        $this->assertCount(12, array_unique($numeros), 'Cada gasto debe recibir un numero distinto: ' . implode(',', $numeros));
    }

    #[Test]
    public function la_numeracion_no_retrcede_al_crear_un_gasto_mayor(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = $this->vehiculo();
        $banco = $this->banco();
        $clasificador = $this->clasificadorDeUnidad('Peaje');

        $alta = fn () => $this->actingAs($admin)->post('/gastos', [
            'id_vehiculo' => $vehiculo,
            'id_clasificador' => $clasificador,
            'monto' => 20,
            'fecha_gasto' => now()->format('Y-m-d'),
            'concepto' => 'Gasto',
            'condicion_pago' => 'CONTADO',
            'id_banco' => $banco,
        ]);

        $alta();
        $primero = DB::table('global.gastos')->latest('id_gasto')->value('nro_documento');

        // Se anula el ultimo documento y se crea otro: el metodo antiguo
        // retrocedia y reutilizaba el numero.
        DB::table('global.gastos')->latest('id_gasto')->update(['estado_pago' => 'Anulado']);

        $alta();
        $segundo = DB::table('global.gastos')->latest('id_gasto')->value('nro_documento');

        $this->assertNotSame($primero, $segundo, 'La secuencia no debe retroceder aunque se anule el ultimo.');
        $this->assertGreaterThan((int) substr($primero, 2), (int) substr($segundo, 2));
    }

    #[Test]
    public function la_numeracion_de_fletes_no_se_repite(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = $this->vehiculo();

        $numeros = [];

        for ($i = 0; $i < 10; $i++) {
            $respuesta = $this->actingAs($admin)->post('/facturacion', [
                'id_vehiculo' => $vehiculo,
                'monto' => 1000 + $i,
                'fecha_ingreso' => now()->format('Y-m-d'),
                'concepto' => 'Flete ' . $i,
            ]);

            $respuesta->assertSessionHasNoErrors();

            // `error` se usa cuando el controlador captura una excepcion: un
            // fallo de insert se enmascararia como si fuera un exito.
            $this->assertArrayNotHasKey(
                'error',
                $respuesta->baseResponse->getSession()->get('flash', []) ?? $respuesta->baseResponse->getSession()->all(),
                "El flete {$i} no se registro: hay un error en sesion."
            );

            $numeros[] = DB::table('global.ingresos')->latest('id_ingreso')->value('nro_documento');
        }

        $this->assertCount(10, array_unique($numeros));
        foreach ($numeros as $n) {
            $this->assertStringStartsWith('I_', $n);
        }
    }
}