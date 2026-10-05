<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use App\Http\Controllers\ReporteController;
use Tests\TestCase;
use Throwable;

/**
 * VERIFICACION END-TO-END
 *
 * Recorre TODAS las rutas de lectura con datos reales de cada modulo y
 * comprueba que:
 *   1. responden 200
 *   2. no lanzan excepciones
 *   3. la plantilla renderiza los datos del dominio (no devuelve una vista
 *      vacia por un campo que dejo de existir)
 *
 * Es la red que detecta el fallo tipico de una refactorizacion: cambiar el
 * nombre de un campo en el controlador y que la vista siga pidiendolo, con
 * la pantalla en blanco o a medias.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private function seedMinimo(): void
    {
        $this->usuarioConRol('admin');
        $this->personal();
        $categoria = \Illuminate\Support\Facades\DB::table('global.categorias_almacen')
            ->insertGetId(['nombre' => 'Lubricantes', 'created_at' => now()], 'id_categoria');
        $vehiculo = $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]);

        \Illuminate\Support\Facades\DB::table('global.tramos')->insert([
            'origen' => 'SANTA CRUZ', 'destino' => 'LA PAZ',
            'kilometros' => 465, 'precio_total' => 4200,
            'precio_dolar_tonelada' => 185, 'created_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('global.inventario')->insertGetId([
            'codigo' => 'LU-001-00001', 'nombre_producto' => 'Aceite SAE 40',
            'id_categoria' => $categoria, 'unidad_medida' => 'GALON',
            'stock_actual' => 24, 'stock_minimo' => 5,
            'precio_compra' => 180, 'ultimo_costo' => 180, 'estado' => 'ACTIVO',
        ], 'id_inventario');

        // Un gasto de unidad y uno general, para que las vistas tengan contenido.
        $this->actingAs($this->usuarioConRol('admin'))->post('/gastos', [
            'id_vehiculo' => $vehiculo,
            'id_clasificador' => $this->clasificadorDeUnidad('Combustible'),
            'monto' => 4200.50,
            'fecha_gasto' => now()->format('Y-m-d'),
            'concepto' => 'Carga de combustible diesel',
            'condicion_pago' => 'CONTADO',
            'id_banco' => $this->banco(),
            'tipo_combustible' => 'Diesel',
            'litros' => 120,
            'precio_por_litro' => 35,
        ]);

        $this->actingAs($this->usuarioConRol('admin'))->post('/gastos-generales', [
            'id_clasificador' => $this->clasificadorGeneral('ServiciosBasicos'),
            'concepto' => 'Factura de energia electrica',
            'monto' => 1850.00,
            'fecha_gasto' => now()->format('Y-m-d'),
            'condicion_pago' => 'CONTADO',
            'id_banco' => $this->banco(),
        ]);

        // Un flete facturado, para que reportes y facturacion tengan datos.
        $this->actingAs($this->usuarioConRol('admin'))->post('/facturacion', [
            'id_vehiculo' => $vehiculo,
            'id_personal' => $this->personal(),
            'monto' => 8900.00,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'concepto' => 'TRANSPORTE DE SOYA',
            'origen' => 'SANTA CRUZ',
            'destino' => 'LA PAZ',
            'toneladas' => 32.5,
            'kilometraje_conducido' => 465,
            'cliente_nombre' => 'INDUSTRIAS OLEAGINOSAS S.A.',
        ]);
    }

/**
 * @return list<array{0:string}>
 */
    public static function formulariosDeGasto(): array
    {
        return [
            ['/gastos/crear'],
            ['/gastos-generales/nuevo'],
        ];
    }

    #[Test]
    public function facturar_un_lote_de_fletes_no_falla_con_la_conexion_global(): void
    {
        $this->seedMinimo();

        // `exists:global.ingresos` hace que Laravel interprete `global` como
        // nombre de conexion y revienta con "Database connection [global]
        // not configured", dejando al operador sin poder facturar nada.
        $pendientes = \Illuminate\Support\Facades\DB::table('global.ingresos')
            ->where('estado_factura', 'PENDIENTE')
            ->pluck('id_ingreso')
            ->all();

        $this->assertNotEmpty($pendientes, 'seedMinimo debe dejar al menos un flete pendiente.');

        $respuesta = $this->actingAs($this->usuarioConRol('admin'))
            ->postJson('/api/facturacion/batch-facturar', [
                'ids' => $pendientes,
                'numero_factura' => 'F-0001',
                'fecha_factura' => now()->format('Y-m-d'),
                'cliente_nombre' => 'INDUSTRIAS OLEAGINOSAS S.A.',
            ]);

        $respuesta->assertOk()->assertJsonPath('success', true);

        $facturados = \Illuminate\Support\Facades\DB::table('global.ingresos')
            ->whereIn('id_ingreso', $pendientes)
            ->where('estado_factura', 'FACTURADA')
            ->count();

        $this->assertSame(count($pendientes), $facturados);
    }

    #[Test]
    public function facturar_rechaza_un_flete_inexistente_con_422_y_no_con_500(): void
    {
        $this->seedMinimo();

        // Si la regla de existencia se resuelve mal, este endpoint revienta
        // con 500 en vez de responder que el id no existe.
        $respuesta = $this->actingAs($this->usuarioConRol('admin'))
            ->postJson('/api/facturacion/batch-facturar', [
                'ids' => [99999999],
                'numero_factura' => 'F-0002',
                'fecha_factura' => now()->format('Y-m-d'),
                'cliente_nombre' => 'CLIENTE',
            ]);

        $respuesta->assertStatus(422)->assertJsonValidationErrors('ids.0');
    }

    /** @return list<array{0:string}> */
    public static function rutasDeLectura(): array
    {
        return array_map(static fn (string $r): array => [$r], [
            '/', '/dashboard', '/documentos',
            '/vehiculos', '/vehiculos/nuevo',
            '/personal', '/personal/nuevo',
            '/almacen', '/almacen/nuevo',
            '/items', '/items/nuevo',
            '/grupos', '/grupos/nuevo',
            '/tramos', '/tramos/nuevo',
            '/facturacion', '/facturacion/nuevo',
            '/gastos/nuevo',
            '/gastos-generales', '/gastos-generales/nuevo',
            '/bancos', '/bancos/nuevo',
            '/proveedores', '/proveedores/nuevo',
            '/reportes', '/clasificadores', '/clasificadores/nuevo',
            '/mantenimiento',
        ]);
    }

    #[Test]
    public function la_lista_de_gastos_vive_dentro_de_mantenimiento(): void
    {
        // `/gastos` no es una pagina propia: el listado sestedtaria dentro de
        // mantenimiento, asi que responde con una redireccion, no con un 404.
        $respuesta = $this->actingAs($this->usuarioConRol('admin'))->get('/gastos');

        $respuesta->assertRedirect(route('mantenimiento.index'));
    }

    /**
     * El panel de carga entre paginas debe ir en todas las pantallas, y antes
     * del contenido: su script inline marca `ds-pending` en el <html> para que
     * la pagina nueva aparezca ya cubierta, sin parpadeo.
     *
     * @return list<array{0:string}>
     */
    public static function rutasConLayout(): array
    {
        return [
            ['/dashboard'],
            ['/vehiculos'],
            ['/almacen'],
            ['/reportes'],
            ['/mantenimiento'],
        ];
    }

    #[Test]
    #[DataProvider('rutasConLayout')]
    public function el_panel_de_carga_entre_paginas_esta_presente_y_va_primero(string $ruta): void
    {
        $this->seedMinimo();

        $contenido = $this->actingAs($this->usuarioConRol('admin'))->get($ruta)->assertOk()->getContent();

        $this->assertStringContainsString('id="dsTransition"', $contenido, "Falta el panel en {$ruta}.");
        $this->assertStringContainsString('ds-tr__truck', $contenido, "Falta la escena animada en {$ruta}.");

        $panel = strpos($contenido, 'id="dsTransition"');
        $cuerpo = strpos($contenido, '<main');

        $this->assertTrue(
            $panel !== false && ($cuerpo === false || $panel < $cuerpo),
            "En {$ruta} el panel se renderiza despues del contenido y se vera parpadear."
        );
    }

    /** @return list<array{0:string}> */
    public static function tiposDeMovimiento(): array
    {
        return [['COMPRA'], ['ENTREGA']];
    }

    #[Test]
    #[DataProvider('tiposDeMovimiento')]
    public function el_formulario_de_movimientos_es_una_pagina_navegable(string $tipo): void
    {
        $this->seedMinimo();

        $contenido = $this->actingAs($this->usuarioConRol('admin'))
            ->get("/almacen/movimiento/{$tipo}")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="formMovimiento"', $contenido);
        $this->assertStringContainsString('name="id_inventario"', $contenido);
        $this->assertStringContainsString('name="cantidad"', $contenido);
        $this->assertStringContainsString('name="fecha_movimiento"', $contenido);

        // El formulario debe ofrecer una forma de volver sin perder el trabajo
        // a medias.
        $this->assertStringContainsString(route('almacen.index'), $contenido);
    }

    #[Test]
    public function el_formulario_de_compra_pide_precio_y_proveedor_y_el_de_entrega_no(): void
    {
        $this->seedMinimo();

        $compra = $this->actingAs($this->usuarioConRol('admin'))
            ->get('/almacen/movimiento/COMPRA')->assertOk()->getContent();

        foreach (['precio_unitario', 'condicion_pago', 'id_proveedor', 'id_banco'] as $campo) {
            $this->assertStringContainsString("name=\"{$campo}\"", $compra, "La compra necesita {$campo}.");
        }

        $entrega = $this->actingAs($this->usuarioConRol('admin'))
            ->get('/almacen/movimiento/ENTREGA')->assertOk()->getContent();

        foreach (['precio_unitario', 'condicion_pago', 'id_proveedor'] as $campo) {
            $this->assertStringNotContainsString("name=\"{$campo}\"", $entrega, "La entrega no lleva {$campo}.");
        }

        // La entrega sí identifica a quién se le entrega.
        $this->assertStringContainsString('name="id_vehiculo"', $entrega);
        $this->assertStringContainsString('name="id_personal"', $entrega);
    }

    #[Test]
    public function un_tipo_de_movimiento_invalido_no_abre_el_formulario(): void
    {
        $this->seedMinimo();

        $this->actingAs($this->usuarioConRol('admin'))
            ->get('/almacen/movimiento/ROBO')
            ->assertRedirect(route('almacen.index'));
    }

    #[Test]
    public function el_listado_de_almacen_enlaza_al_formuario_y_no_tiene_modal_de_movimientos(): void
    {
        $contenido = $this->actingAs($this->usuarioConRol('admin'))->get('/almacen')->assertOk()->getContent();

        $this->assertStringContainsString(route('almacen.movimiento', 'COMPRA'), $contenido);
        $this->assertStringContainsString(route('almacen.movimiento', 'ENTREGA'), $contenido);

        $this->assertStringNotContainsString('id="modalMovimiento"', $contenido, 'El modal de movimientos se reemplazo por una pagina.');
        $this->assertStringNotContainsString('abrirModalMovimiento', $contenido);
    }

    #[Test]
    public function el_listado_de_almacen_escapa_los_datos_que_pinta_con_innerHTML(): void
    {
        // Si un nombre de producto trae HTML, debe llegar al DOM como texto y
        // no ejecutarse: las tablas se arman con template literals.
        $this->seedMinimo();

        \Illuminate\Support\Facades\DB::table('global.inventario')
            ->update(['nombre_producto' => '<img src=x onerror=alert(1)>']);

        $contenido = $this->actingAs($this->usuarioConRol('admin'))->get('/almacen')->getContent();

        // `esc`/`txt`/`bs` viven en app.js, no en la vista: lo que se verifica
        // aquí es que ninguna interpolación quede cruda.
        $this->assertDoesNotMatchRegularExpression(
            '/\$\{(p|m)\.(nombre_producto|codigo_barras|categoria|unidad_medida|codigo_lote|proveedor|conductor|placa_vehiculo)\}/',
            $contenido,
            'Hay datos de la base interpolados sin escapar en el listado de almacen.'
        );
    }

    #[Test]
    public function el_menu_agrupa_catalogos_y_transacciones_y_saco_los_atajos(): void
    {
        $contenido = $this->actingAs($this->usuarioConRol('admin'))->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('CATÁLOGOS', $contenido);
        $this->assertStringContainsString('TRANSACCIONES', $contenido);

        // Facturación y Reportes son accesos directos, no desplegables.
        $this->assertStringNotContainsString('category-facturación', $contenido);
        $this->assertStringNotContainsString('category-reportes', $contenido);

        // Cada enlace debe seguir dentro de la sección correcta.
        $this->assertMatchesRegularExpression(
            '/CATÁLOGOS.*?PERSONAL.*?RUTAS/s',
            $contenido,
            'Personal y Rutas deben caer dentro de Catálogos.'
        );

        $this->assertMatchesRegularExpression(
            '/TRANSACCIONES.*?UNIDADES.*?GASTOS GENERALES.*?CLASIFICADOR DE GASTOS/s',
            $contenido,
            'Transacciones debe contener Unidades, Gastos Generales y el Clasificador.'
        );
    }

    #[Test]
    public function el_menu_no_ofrece_a_lectura_lo_que_solo_usa_el_admin(): void
    {
        $contenido = $this->actingAs($this->usuarioConRol('lectura'))->get('/dashboard')->assertOk()->getContent();

        // Usuarios y Configuración son de solo admin y no aparecen. El
        // clasificador sí se enlaza: lectura puede consultarlo, lo que no
        // puede es modificarlo, y eso se resuelve en la vista, no ocultando
        // el catálogo entero.
        $this->assertStringNotContainsString(route('usuarios.index'), $contenido);
        $this->assertStringNotContainsString(route('configuracion.index'), $contenido);

        $this->assertStringContainsString(route('clasificadores.index'), $contenido);

        // Los catálogos sí se consultan con solo lectura: deben estar.
        $this->assertStringContainsString(route('personal.index'), $contenido);
        $this->assertStringContainsString(route('almacen.index'), $contenido);
    }

    #[Test]
    public function el_panel_de_carga_esta_tambien_en_el_login(): void
    {
        // El login usa `layouts.master-no-nav`, que es un layout aparte.
        $contenido = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('id="dsTransition"', $contenido);
    }

    #[Test]
    public function la_csp_permite_el_estilo_y_el_script_inline_del_panel_de_carga(): void
    {
        $contenido = $this->actingAs($this->usuarioConRol('admin'))->get('/dashboard')->assertOk();

        $csp = (string) $contenido->headers->get('Content-Security-Policy');

        // El componente trae <style> y <script> embebidos: sin 'unsafe-inline'
        // el navegador los bloquea y la transicion no ocurre.
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
    }

    #[Test]
    #[DataProvider('rutasDeLectura')]
    public function cada_ruta_de_lectura_responde_sin_errores(string $ruta): void
    {
        $this->seedMinimo();

        $respuesta = $this->actingAs($this->usuarioConRol('admin'))->get($ruta);

        $respuesta->assertOk();

        // Una vista que revienta al renderizar devuelve HTML parcial o vacio.
        $this->assertStringNotContainsString('Whoops', $respuesta->getContent());
        $this->assertStringNotContainsString('Undefined variable', $respuesta->getContent());
        $this->assertGreaterThan(
            500,
            strlen($respuesta->getContent()),
            "La vista de {$ruta} renderizo suspiciously poco."
        );
    }

    /** @return list<array{0:string}> */
    public static function endpointsDeLectura(): array
    {
        return array_map(static fn (string $r): array => [$r], [
            '/api/vehiculos', '/api/personal',
            '/api/dashboard/stats', '/api/almacen',
            '/api/almacen/categorias', '/api/almacen/movimientos',
            '/api/lotes/ultimo',
            '/api/items', '/api/grupos', '/api/tramos', '/api/bancos', '/api/proveedores',
            '/api/gastos', '/api/gastos-generales', '/api/facturacion',
            '/api/facturacion/listado', '/api/facturacion/pendientes',
            '/api/clasificadores', '/api/clasificadores/1', '/api/config',
            '/api/reportes/filtro', '/api/reportes/financiero',
            '/api/reportes/almacen', '/api/reportes/estadisticas',
        ]);
    }

    #[Test]
    #[DataProvider('endpointsDeLectura')]
    public function cada_endpoint_devuelve_json_valido(string $endpoint): void
    {
        $this->seedMinimo();

        $respuesta = $this->actingAs($this->usuarioConRol('admin'))->getJson($endpoint);

        $respuesta->assertOk();

        $json = $respuesta->json();

        // Contrato comun de la pseudo-API.
        $this->assertIsArray($json, "{$endpoint} no devolvio un objeto JSON.");
        $this->assertArrayHasKey('success', $json, "{$endpoint} no devolvio la clave 'success'.");
        $this->assertTrue($json['success'], "{$endpoint} devolvio success=false.");
    }

    // =================================================================
    // LOS DATOS SIGUEN SIENDO VISIBLES
    // =================================================================

    #[Test]
    public function el_dashboard_muestra_las_unidades_con_su_conductor(): void
    {
        $this->seedMinimo();

        $contenido = $this->actingAs($this->usuarioConRol('admin'))->get('/dashboard')->getContent();

        $placa = \Illuminate\Support\Facades\DB::table('global.vehiculos')->value('placa_vehiculo');

        $this->assertStringContainsString('PANEL DE CONTROL', $contenido);
        $this->assertStringContainsString('Monitoreo de Flota Activa', $contenido);

        // La placa se carga por fetch: lo que se verifica es que el endpoint
        // que la alimenta devuelve el dato.
        $json = $this->actingAs($this->usuarioConRol('admin'))->getJson('/api/vehiculos')->json('data');

        $this->assertNotEmpty($json);
        // `TestResponse::json()` decodifica como arrays asociativos.
        $this->assertSame($placa, $json[0]['placa_vehiculo']);
        $this->assertArrayHasKey('conductor', $json[0]);
        $this->assertArrayHasKey('total_ingresos', $json[0]);
        $this->assertArrayHasKey('total_gastos', $json[0]);
    }

    /**
     * ESQUEMA QUE LA APP ESCRIBE vs EL QUE EXISTE
     *
     * Divergencia real entre el historial de migraciones y la base: la
     * migracion `create_ingresos_table` declara `ingresos.id_tramo` y figura
     * aplicada, pero la columna no existia en produccion. Como el
     * StoreFleteRequest la envia siempre, registrar un flete fallaba con
     *
     *     42703 column "id_tramo" of relation "ingresos" does not exist
     *
     * Estos tests recorren el esquema y comparan contra lo que el codigo
     * escribe, para que una divergencia like esa salte en el pipeline y no
     * la descubra el operador al primer alta.
     *
     * @return list<array{0:string,1:list<string>}>
     */
    public static function columnasQueEscribeElCodigo(): array
    {
        return [
            ['global.ingresos', [
                // Solo lo que StoreFleteRequest devuelve en
                // datosNormalizados() mas lo que agrega el controller
                // (nro_documento, fecha_registro). `conductor_asignado` y
                // `id_tramo` los acepta la base pero no los manda el
                // formulario: se documentan, no se escriben en el alta.
                'id_vehiculo', 'id_personal', 'nro_documento', 'concepto',
                'monto', 'fecha_ingreso', 'fecha_vencimiento', 'observaciones',
                'cliente_nombre', 'cliente_nit', 'cliente_telefono', 'origen', 'destino',
                'toneladas', 'kilometraje_conducido', 'tipo_pago',
                'estado_factura', 'numero_factura', 'fecha_factura', 'fecha_registro',
            ]],

            ['global.gastos', [
                'id_vehiculo', 'id_clasificador', 'monto', 'fecha_gasto', 'concepto',
                'condicion_pago', 'id_banco', 'es_devolucion', 'nro_documento',
                'creado_por', 'fecha_registro',
            ]],

            ['global.gastos_generales', [
                'id_clasificador', 'concepto', 'monto', 'fecha_gasto',
                'condicion_pago', 'id_banco', 'es_devolucion', 'nro_documento',
            ]],

            ['global.movimientos_inventario', [
                'id_inventario', 'id_lote', 'tipo_movimiento', 'cantidad',
                'costo_unitario', 'costo_total', 'id_vehiculo', 'id_personal',
                'id_proveedor', 'id_banco', 'documento_numero', 'fecha_movimiento',
                'condicion_pago', 'registrado_por',
            ]],

            ['global.lotes', [
                'id_inventario', 'codigo_lote', 'cantidad_inicial',
                'cantidad_actual', 'precio_compra', 'estado', 'created_at',
            ]],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('columnasQueEscribeElCodigo')]
    public function toda_columna_que_el_codigo_escribe_existe(string $tabla, array $columnas): void
    {
        $existentes = array_map(
            static fn ($r) => $r->column_name,
            \Illuminate\Support\Facades\DB::select(
                'SELECT column_name FROM information_schema.columns
                 WHERE table_schema = ? AND table_name = ?',
                ['global', explode('.', $tabla)[1]]
            )
        );

        $faltantes = array_values(array_diff($columnas, $existentes));

        $this->assertSame(
            [],
            $faltantes,
            "En {$tabla} faltan columnas que la app escribe: " . implode(', ', $faltantes)
            . ". Registrar desde el formulario daria 42703."
        );
    }

    #[Test]
    public function el_indice_y_la_clave_foranea_de_id_tramo_existen(): void
    {
        // `indices_e_integridad` los creo solo si la columna existia, asi que
        // se saltaron sin avisar cuando faltaba. `id_tramo` no la escribe el
        // alta de fletes, pero si la leen reportes y filtros.
        $indice = \Illuminate\Support\Facades\DB::selectOne("
            SELECT 1 FROM pg_indexes
            WHERE schemaname = 'global' AND tablename = 'ingresos'
              AND indexname = 'ingresos_tramo_idx'
        ");

        $this->assertNotNull($indice, 'Falta el indice ingresos_tramo_idx.');

        $fk = \Illuminate\Support\Facades\DB::selectOne("
            SELECT 1 FROM information_schema.table_constraints
            WHERE constraint_schema = 'global' AND table_name = 'ingresos'
              AND constraint_name = 'fk_ingreso_tramo'
        ");

        $this->assertNotNull($fk, 'Falta la clave foranea fk_ingreso_tramo.');
    }

    #[Test]
    public function la_regla_de_alcance_del_clasificador_se_respeta(): void
    {
        // `RefreshDatabase` vacia la base entre tests, asi que sin esto no habria
        // nada que comprobar.
        $this->seedMinimo();

        // El seed no garantiza un clasificador de alcance general puro: el helper
        // los crea con `afecta_unidad`, asi que aqui se aseguran dos.
        $soloGeneralId = $this->clasificador([
            'tipo_gasto' => 'CajaChica',
            'afecta_unidad' => false,
            'afecta_general' => true,
        ]);

        $soloUnidadId = $this->clasificador([
            'tipo_gasto' => 'Peaje',
            'afecta_unidad' => true,
            'afecta_general' => false,
        ]);

        $svc = app(\App\Services\ClasificadorGastoService::class);

        $soloGeneral = \Illuminate\Support\Facades\DB::table('global.clasificador_gastos')
            ->where('id_clasificador', $soloGeneralId)->first();

        $soloUnidad = \Illuminate\Support\Facades\DB::table('global.clasificador_gastos')
            ->where('id_clasificador', $soloUnidadId)->first();

        $this->assertNotNull($soloGeneral, 'Se creo un clasificador de alcance general.');
        $this->assertNotNull($soloUnidad, 'Se creo un clasificador de alcance unidad.');

        // Un clasificador de alcance general NO puede usarse en un gasto de
        // unidad: es la regla funcional del modulo.
        $rechazado = false;

        try {
            $svc->validarAlcance($soloGeneral->id_clasificador, 'unidad');
        } catch (Throwable) {
            $rechazado = true;
        }

        $this->assertTrue(
            $rechazado,
            'Un clasificador de alcance general deberia rechazarse en gastos de unidad.'
        );

        // Y uno de unidad tampoco en gastos generales.
        $rechazado = false;

        try {
            $svc->validarAlcance($soloUnidad->id_clasificador, 'general');
        } catch (Throwable) {
            $rechazado = true;
        }

        $this->assertTrue(
            $rechazado,
            'Un clasificador de alcance unidad deberia rechazarse en gastos generales.'
        );

        // En su propio alcance, si: `validarAlcance` devuelve la fila o
        // lanza, no devuelve null.
        $this->assertSame(
            'CajaChica',
            $svc->validarAlcance($soloGeneral->id_clasificador, 'general')->tipo_gasto
        );

        $this->assertSame(
            $soloUnidad->tipo_gasto,
            $svc->validarAlcance($soloUnidad->id_clasificador, 'unidad')->tipo_gasto
        );
    }

    #[Test]
    public function el_endpoint_de_next_code_exige_un_grupo_valido(): void
    {
        $this->seedMinimo();

        $categoria = \Illuminate\Support\Facades\DB::table('global.categorias_almacen')->value('id_categoria');

        $this->actingAs($this->usuarioConRol('admin'))
            ->getJson('/api/almacen/next-code?id_categoria=' . $categoria)
            ->assertOk()
            ->assertJsonPath('success', true);

        // Sin grupo no hay codigo que generar: 422, no un 500.
        $this->actingAs($this->usuarioConRol('admin'))
            ->getJson('/api/almacen/next-code')
            ->assertStatus(422);
    }

    #[Test]
    public function la_api_de_vehiculos_conserva_los_campos_que_pinta_la_vista(): void
    {
        $this->seedMinimo();

        $v = $this->actingAs($this->usuarioConRol('admin'))->getJson('/api/vehiculos')->json('data.0');

        // Contrato exacto que dashboard/index.blade.php interpola.
        foreach ([
            'id_vehiculo', 'placa_vehiculo', 'estado', 'tipo_vehiculo',
            'conductor', 'capacidad', 'total_ingresos', 'total_gastos',
        ] as $campo) {
            $this->assertArrayHasKey($campo, $v, "El dashboard necesita el campo {$campo}.");
        }
    }

    #[Test]
    public function el_reporte_conserva_los_campos_que_pinta_el_dashboard(): void
    {
        $this->seedMinimo();

        $data = $this->actingAs($this->usuarioConRol('admin'))
            ->getJson('/api/reportes/filtro?fecha_inicio=' . now()->format('Y-m-01') . '&fecha_fin=' . now()->format('Y-m-d'))
            ->json('data');

        $this->assertNotEmpty($data, 'El reporte debe traer movimientos del periodo.');

        foreach ([
            'tipo_registro', 'id', 'fecha', 'concepto', 'egreso', 'ingreso',
            'observaciones', 'placa_vehiculo', 'id_vehiculo', 'tipo_gasto',
            'cantidad', 'nro_documento',
        ] as $campo) {
            $this->assertArrayHasKey($campo, $data[0], "El reporte necesita el campo {$campo}.");
        }
    }

    #[Test]
    public function el_reporte_de_almacen_trae_los_datos_de_valorizacion(): void
    {
        $this->seedMinimo();

        $data = $this->actingAs($this->usuarioConRol('admin'))->getJson('/api/reportes/almacen')->json('data');

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('categoria', $data[0]);
        $this->assertArrayHasKey('total_items', $data[0]);
        $this->assertArrayHasKey('valor_total', $data[0]);
    }

    #[Test]
    public function el_clasificador_muestra_las_tres_dimensiones(): void
    {
        $contenido = $this->actingAs($this->usuarioConRol('admin'))->get('/clasificadores')->getContent();

        $this->assertStringContainsString('CLASIFICADOR DE GASTOS', $contenido);
        $this->assertStringContainsString('CÓDIGO ID', $contenido);
        $this->assertStringContainsString('DESCRIPCIÓN', $contenido);
        $this->assertStringContainsString('TIPO DE GASTO', $contenido);
        $this->assertStringContainsString('AFECTA UNIDAD', $contenido);
        $this->assertStringContainsString('AFECTA GENERAL', $contenido);

        // Las acciones llevan texto, no solo un icono: el operador tiene que
        // poder leer qué hace cada botón sin interpretar el glifo.
        $this->assertStringContainsString('EDITAR', $contenido);
        $this->assertStringContainsString('QUITAR', $contenido);
    }

    #[Test]
    public function el_formulario_de_clasificador_ofrece_los_tipos_canonicos(): void
    {
        $contenido = $this->actingAs($this->usuarioConRol('admin'))->get('/clasificadores/nuevo')->getContent();

        foreach (\App\Enums\TipoGasto::values() as $tipo) {
            $this->assertStringContainsString('value="' . $tipo . '"', $contenido, "Falta el tipo {$tipo}.");
        }
    }

    #[Test]
    public function la_vista_de_gastos_generales_ofrece_los_clasificadores_generales(): void
    {
        $contenido = $this->actingAs($this->usuarioConRol('admin'))->get('/gastos-generales/nuevo')->getContent();

        $this->assertStringContainsString('CLASIFICADOR DE GASTO', $contenido);
        // Solo los de alcance general (texto de la propia plantilla).
        $this->assertStringContainsString('afectan en forma general', $contenido);
    }

    #[Test]
    public function el_estado_de_cuenta_del_banco_muestra_los_movimientos(): void
    {
        $this->seedMinimo();

        $bancoId = \Illuminate\Support\Facades\DB::table('global.gastos')->whereNotNull('id_banco')->value('id_banco');

        $contenido = $this->actingAs($this->usuarioConRol('admin'))
            ->get('/bancos/' . $bancoId . '/estado')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Carga de combustible diesel', $contenido);
    }

    #[Test]
    public function la_vista_de_reportes_se_abre_con_los_graficos(): void
    {
        $this->seedMinimo();

        $contenido = $this->actingAs($this->usuarioConRol('admin'))->get('/reportes')->assertOk()->getContent();

        $this->assertStringContainsString('api/reportes/estadisticas', $contenido);
        $this->assertStringContainsString('new Chart', $contenido);
    }

    #[Test]
    public function el_pdf_del_reporte_se_genera(): void
    {
        $this->seedMinimo();

        $this->actingAs($this->usuarioConRol('admin'))
            ->get('/reportes/pdf?fecha_inicio=' . now()->format('Y-m-01') . '&fecha_fin=' . now()->format('Y-m-d'))
            ->assertOk();

        $this->actingAs($this->usuarioConRol('admin'))
            ->get('/reportes/imprimir?fecha_inicio=' . now()->format('Y-m-01') . '&fecha_fin=' . now()->format('Y-m-d'))
            ->assertOk()
            ->assertSee('window.print', false);
    }

    #[Test]
    public function el_estado_de_cuenta_del_proveedor_no_rompe(): void
    {
        $this->seedMinimo();

        $proveedorId = $this->proveedor();

        // La ruta es web, no de la API: ProveedorController@estado responde HTML/JSON
        // segun el encabezado Accept.
        $this->actingAs($this->usuarioConRol('admin'))
            ->getJson('/proveedores/' . $proveedorId . '/estado')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    #[Test]
    public function el_select_de_ruta_del_flete_tiene_name_y_preselecciona_al_editar(): void
    {
        $this->seedMinimo();

        $tramoId = \Illuminate\Support\Facades\DB::table('global.tramos')->value('id_tramo');

        $this->actingAs($this->usuarioConRol('admin'))->post('/facturacion', [
            'id_vehiculo' => $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]),
            'id_tramo' => $tramoId,
            'monto' => 1000,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'concepto' => 'TRANSPORTE DE SOYA',
            'toneladas' => 10,
        ]);

        $alta = $this->actingAs($this->usuarioConRol('admin'))->get('/facturacion/nuevo');
        $alta->assertOk();
        $this->assertStringContainsString('name="id_tramo"', $alta->getContent());
        $this->assertStringContainsString('id="form_id_tramo"', $alta->getContent());

        // Editar debe devolver el tramo ya elegido, si no el operador ve
        // "SELECCIONE RUTA" con origen y destino de la ruta correcta.
        $ingresoId = \Illuminate\Support\Facades\DB::table('global.ingresos')
            ->where('id_tramo', $tramoId)
            ->value('id_ingreso');

        $editar = $this->actingAs($this->usuarioConRol('admin'))->get("/facturacion/{$ingresoId}/editar");
        $editar->assertOk();
        $this->assertMatchesRegularExpression(
            '/<option value="' . preg_quote((string) $tramoId, '/') . '".*selected/s',
            $editar->getContent()
        );
    }

    #[Test]
    public function la_ruta_elegida_en_el_flete_se_guarda_al_crear_y_al_editar(): void
    {
        $this->seedMinimo();

        $tramos = \Illuminate\Support\Facades\DB::table('global.tramos')
            ->orderBy('id_tramo')
            ->get(['id_tramo', 'origen', 'destino']);

        $vehiculo = $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]);

        $this->actingAs($this->usuarioConRol('admin'))->post('/facturacion', [
            'id_vehiculo' => $vehiculo,
            'id_tramo' => $tramos[0]->id_tramo,
            'monto' => 1000,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'toneladas' => 10,
        ]);

        $ingresos = \Illuminate\Support\Facades\DB::table('global.ingresos')->get();
        $guardado = $ingresos->firstWhere('id_tramo', $tramos[0]->id_tramo);

        // Sin `name` en el select, la ruta nunca llegaba al servidor.
        $this->assertNotNull($guardado, 'El tramo debe persistirse al registrar.');
        $this->assertSame($tramos[0]->origen, $guardado->origen);
        $this->assertSame($tramos[0]->destino, $guardado->destino);

        // Cambiar la ruta de un flete debe actualizarse, no descartarse.
        if (isset($tramos[1])) {
            $this->actingAs($this->usuarioConRol('admin'))->put("/facturacion/{$guardado->id_ingreso}", [
                'id_vehiculo' => $vehiculo,
                'id_tramo' => $tramos[1]->id_tramo,
                'monto' => 2000,
                'fecha_ingreso' => now()->format('Y-m-d'),
                'toneladas' => 20,
            ]);

            $actual = \Illuminate\Support\Facades\DB::table('global.ingresos')
                ->where('id_ingreso', $guardado->id_ingreso)
                ->first();

            $this->assertEquals($tramos[1]->id_tramo, $actual->id_tramo);
            $this->assertSame($tramos[1]->origen, $actual->origen);
            $this->assertSame($tramos[1]->destino, $actual->destino);
        }
    }

    #[Test]
    public function el_calculo_del_flete_recalcula_al_volver_a_elegir_la_misma_ruta(): void
    {
        $this->seedMinimo();

        $js = file_get_contents(base_path('resources/views/facturacion/form.blade.php'));

        // Volver a marcar la MISMA opcion no dispara `onchange`: sin este
        // boton el operador no puede recargar una ruta ya seleccionada.
        $this->assertStringContainsString('APLICAR RUTA', $js);

        // Antes el monto solo se recalculaba con toneladas > 0, dejando el
        // total de la carga anterior cuando el campo quedaba vacio. La
        // guarda sigue, pero ahora tiene salida.
        $this->assertMatchesRegularExpression(
            '/if \(toneladas > 0 && precioTon > 0\) \{.*?return;.*?\}/s',
            $js
        );
        $this->assertStringContainsString("monto.value = '0.00'", $js);

        // Volver a "SELECCIONE RUTA..." debe limpiar, no dejar datos viejos.
        $this->assertStringContainsString("if (!opt || !opt.value)", $js);
    }

    #[Test]
    public function el_modal_de_nuevo_flete_expone_los_datos_de_la_ruta(): void
    {
        $this->seedMinimo();

        $html = $this->actingAs($this->usuarioConRol('admin'))->get('/facturacion')->assertOk()->getContent();

        // El modal construye sus <option> por JS. Sin `data-origen` y
        // `data-destino` el select mostraba la ruta pero los campos de abajo
        // se quedaban en su placeholder.
        $this->assertStringContainsString('data-origen="${window.esc(t.origen', $html);
        $this->assertStringContainsString('data-destino="${window.esc(t.destino', $html);

        // El modal arma el envio a mano y se le habia olvidado el tramo:
        // la ruta se elegia en pantalla pero no se guardaba.
        $this->assertMatchesRegularExpression(
            '/id_tramo:\s*document\.getElementById\(.nf_id_tramo.\)/',
            $html
        );

        // Boton para recargar la MISMA ruta, que no dispara `onchange`.
        $this->assertStringContainsString('btnAplicarRutaNF', $html);
    }

    #[Test]
    public function el_modal_de_nuevo_flete_recalcula_el_monto_siempre(): void
    {
        $this->seedMinimo();

        $html = $this->actingAs($this->usuarioConRol('admin'))->get('/facturacion')->assertOk()->getContent();

        // La guarda `ton > 0 && precioTon > 0` se conserva, pero ahora tiene
        // salida: antes no habia `else` y el monto se quedaba con el de la
        // carga anterior cuando las toneladas estaban vacias.
        $this->assertMatchesRegularExpression(
            '/if \(ton > 0 && precioTon > 0\) \{.*?return;.*?\}/s',
            $html
        );

        // Sin datos suficientes el total va a 0, no un numero viejo.
        $this->assertStringContainsString("monto.value = '0';", $html);

        // Volver a "SELECCIONE RUTA..." tiene que limpiar los campos.
        $this->assertStringContainsString('if (!opt || !opt.value)', $html);

        // El boton abre el modal: debe limpiar tambien lo que `reset()` deja.
        $this->assertStringContainsString("['nf_origen', 'nf_destino', 'nf_precio_ton']", $html);
    }

    #[Test]
    public function el_modal_de_flete_del_dashboard_calcula_el_monto_desde_las_toneladas(): void
    {
        $this->seedMinimo();

        $html = $this->actingAs($this->usuarioConRol('admin'))->get('/dashboard')->assertOk()->getContent();

        // El bug real: el modal restaurava el monto desde `localStorage` y
        // jamas recalculaba, asi que abria mostrando el total del flete
        // anterior y el operador lo enviaba sin tocar nada.
        $this->assertStringNotContainsString("savedValues.fd_monto", $html);

        // Y ahora el monto se deriva, no se restaura.
        $this->assertMatchesRegularExpression(
            '/function recalcularMontoDash\(\).*?ton > 0 && precioTon > 0.*?return;.*?\}/s',
            $html
        );
        $this->assertStringContainsString("monto.value = '0';", $html);

        // Al abrir el modal se aplica la ruta guardada y se recalcula.
        $this->assertStringContainsString('seleccionarRuta(selTramo)', $html);
        $this->assertStringContainsString('recalcularMontoDash();', $html);

        // Sin esto la busqueda en `tramos` fallaba en silencio si el fetch
        // todavia no habia vuelto, y no rellenaba origen ni destino.
        $this->assertStringContainsString('data-origen="${window.esc(t.origen', $html);
        $this->assertStringContainsString('data-destino="${window.esc(t.destino', $html);

        // El envio se arma a mano y faltaba el tramo.
        $this->assertMatchesRegularExpression(
            '/id_tramo:\s*document\.getElementById\(.fd_id_tramo.\)/',
            $html
        );

        // El monto no debe guardarse: es derivado de toneladas x Bs/TON.
        $this->assertStringContainsString("const derivados = ['fd_monto'", $html);
    }

    #[Test]
    public function los_tres_formularios_de_flete_calculan_el_monto_igual(): void
    {
        $this->seedMinimo();

        $admin = $this->usuarioConRol('admin');

        // Tres copias del mismo formulario: pagina, modal de facturacion y
        // modal del dashboard. Las tres Tenian el guard `ton > 0 && precioTon
        // > 0` sin salida, y por eso el monto se quedaba con el valor viejo.
        $pantallas = [
            '/facturacion/nuevo' => 'form_toneladas',
            '/facturacion' => 'nf_toneladas',
            '/dashboard' => 'fd_toneladas',
        ];

        foreach ($pantallas as $ruta => $campo) {
            $html = $this->actingAs($admin)->get($ruta)->assertOk()->getContent();

            $this->assertStringContainsString($campo, $html, "{$ruta} debe seguir pidiendo las toneladas.");

            // El calculo existe y termina en un `return`, dejando una salida
            // que pone el monto en cero cuando faltan datos.
            $this->assertMatchesRegularExpression(
                '/function recalcularMonto\w*\(\).*?> 0 &&.*?return;.*?\}/s',
                $html,
                "{$ruta}: el calculo del monto debe tener salida."
            );
        }
    }

    #[Test]
    public function borrar_una_ruta_no_borra_los_fletes_que_la_usan(): void
    {
        $this->seedMinimo();

        $tramoId = \Illuminate\Support\Facades\DB::table('global.tramos')->value('id_tramo');

        $this->actingAs($this->usuarioConRol('admin'))->post('/facturacion', [
            'id_vehiculo' => $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]),
            'id_tramo' => $tramoId,
            'monto' => 1000,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'toneladas' => 10,
        ]);

        $flete = \Illuminate\Support\Facades\DB::table('global.ingresos')
            ->where('id_tramo', $tramoId)
            ->first();

        $this->assertNotNull($flete, 'El flete debe quedar apuntando a la ruta.');

        $this->actingAs($this->usuarioConRol('admin'))
            ->delete('/tramos/' . $tramoId)
            ->assertRedirect();

        $tras = \Illuminate\Support\Facades\DB::table('global.ingresos')
            ->where('id_ingreso', $flete->id_ingreso)
            ->first();

        // El flete es un documento con valor contable: no puede desaparecer
        // por borrar un dato tarifario de referencia.
        $this->assertNotNull($tras, 'Borrar una ruta no debe borrar fletes.');
        $this->assertNull($tras->id_tramo, 'Queda sin referencia a la ruta.');

        // Y conserva su origen/destino historicos, que es justo por eso que
        // los reportes siguen leyendo el texto y no dependen de la ruta.
        $this->assertSame('SANTA CRUZ', $tras->origen);
        $this->assertSame('LA PAZ', $tras->destino);
    }

    #[Test]
    public function los_reportes_priorizan_la_ruta_del_catalogo_y_caen_al_texto(): void
    {
        $this->seedMinimo();

        $sql = file_get_contents(base_path('app/Http/Controllers/ReporteController.php'));

        // LEFT JOIN: un flete historico sin `id_tramo` tiene que seguir
        // saliendo, con su propio texto.
        $this->assertStringContainsString("leftJoin('global.tramos as tr'", $sql);

        // Y el texto del catalogo tiene prioridad sobre el guardado.
        $this->assertStringContainsString("COALESCE(tr.origen, i.origen, '') as origen", $sql);
        $this->assertStringContainsString("COALESCE(tr.destino, i.destino, '') as destino", $sql);
    }

    /**
 * Corre la migracion de enlace aunque `RefreshDatabase` ya la haya aplicado.
 *
 * `artisan migrate` no serviria: `RefreshDatabase` migra la base antes de
 * cada test y deja el registro de migraciones al dia, asi que el comando
 * responderia "Nothing to migrate" sin ejecutar el `up()`.
 */
private function ejecutarEnlaceDeRutas(): void
{
    $archivo = database_path('migrations/2026_10_05_000001_enlazar_fletes_con_ruta.php');

    $this->assertFileExists($archivo, 'La migracion de enlace debe existir.');

    $migracion = require $archivo;
    $migracion->up();
}

#[Test]
    public function el_backfill_enlaza_solo_las_rutas_que_coinciden_exactamente(): void
    {
        $this->seedMinimo();

        // SANTA CRUZ -> LA PAZ esta en el catalogo como 'SANTA CRUZ'/'LA PAZ'.
        $coincide = \Illuminate\Support\Facades\DB::table('global.ingresos')->insertGetId([
            'id_vehiculo' => $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]),
            'concepto' => 'TRANSPORTE DE SOYA',
            'monto' => 1000,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'origen' => 'SANTA CRUZ',
            'destino' => 'LA PAZ',
            'toneladas' => 10,
            'estado_factura' => 'PENDIENTE',
        ], 'id_ingreso');

        // Mismo origen, destino que NO existe en el catalogo.
        $noCoincide = \Illuminate\Support\Facades\DB::table('global.ingresos')->insertGetId([
            'id_vehiculo' => $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]),
            'concepto' => 'TRANSPORTE DE SOYA',
            'monto' => 1000,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'origen' => 'SANTA CRUZ',
            'destino' => 'BUENA VISTA',
            'toneladas' => 10,
            'estado_factura' => 'PENDIENTE',
        ], 'id_ingreso');

        // Sin origen ni destino: no hay informacion para deducir la ruta.
        $sinTexto = \Illuminate\Support\Facades\DB::table('global.ingresos')->insertGetId([
            'id_vehiculo' => $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]),
            'concepto' => 'TRANSPORTE DE SOYA',
            'monto' => 1000,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'toneladas' => 10,
            'estado_factura' => 'PENDIENTE',
        ], 'id_ingreso');

        // Diferencia solo en mayusculas: NO debe enlazarse. Enlazar por
        // resemblance seria adivinar la ruta, y una ruta mal puesta mueve el
        // flete de reporte.
        $distintoCase = \Illuminate\Support\Facades\DB::table('global.ingresos')->insertGetId([
            'id_vehiculo' => $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]),
            'concepto' => 'TRANSPORTE DE SOYA',
            'monto' => 1000,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'origen' => 'santa cruz',
            'destino' => 'la paz',
            'toneladas' => 10,
            'estado_factura' => 'PENDIENTE',
        ], 'id_ingreso');

        $this->ejecutarEnlaceDeRutas();

        $tramo = \Illuminate\Support\Facades\DB::table('global.tramos')->value('id_tramo');

        $leido = static fn ($id) => \Illuminate\Support\Facades\DB::table('global.ingresos')
            ->where('id_ingreso', $id)
            ->first();

        $this->assertEquals($tramo, $leido($coincide)->id_tramo, 'Debe enlazar la coincidencia exacta.');

        foreach ([$noCoincide, $sinTexto, $distintoCase] as $id) {
            $this->assertNull(
                $leido($id)->id_tramo,
                'No debe enlazar un flete cuyo texto no coincide exacto con el catalogo.'
            );
        }
    }

    #[Test]
    public function el_backfill_no_pisa_un_flete_que_ya_tenia_ruta(): void
    {
        $this->seedMinimo();

        $vehiculo = $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]);

        // Dos rutas con distinto par origen/destino.
        $rutaBuena = \Illuminate\Support\Facades\DB::table('global.tramos')->insertGetId([
            'origen' => 'COCHABAMBA', 'destino' => 'SUCRE',
            'kilometros' => 200, 'precio_total' => 100,
            'precio_dolar_tonelada' => 50, 'created_at' => now(),
        ], 'id_tramo');

        // Flete ya enlazado a mano por el operador.
        $yaEnlazado = \Illuminate\Support\Facades\DB::table('global.ingresos')->insertGetId([
            'id_vehiculo' => $vehiculo,
            'concepto' => 'TRANSPORTE DE SOYA',
            'monto' => 1000,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'origen' => 'SANTA CRUZ', 'destino' => 'LA PAZ',
            'id_tramo' => $rutaBuena,
            'toneladas' => 10,
            'estado_factura' => 'PENDIENTE',
        ], 'id_ingreso');

        $this->ejecutarEnlaceDeRutas();

        $actual = \Illuminate\Support\Facades\DB::table('global.ingresos')
            ->where('id_ingreso', $yaEnlazado)
            ->first();

        // Su texto dice SANTA CRUZ -> LA PAZ, que si existe en el catalogo,
        // pero el operador lo asocio a mano a COCHABAMBA -> SUCRE. La
        // migracion no debe sobreescribir esa decision.
        $this->assertEquals($rutaBuena, $actual->id_tramo, 'No debe pisar una ruta ya asignada.');
    }

    #[Test]
    public function el_reporte_muestra_la_ruta_del_catalogo_y_tambien_la_de_los_huerfanos(): void
    {
        $this->seedMinimo();

        $vehiculo = $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]);
        $tramo = \Illuminate\Support\Facades\DB::table('global.tramos')->value('id_tramo');

        // Flete enlazado: el reporte debe mostrar el texto de la ruta.
        $enlazado = \Illuminate\Support\Facades\DB::table('global.ingresos')->insertGetId([
            'id_vehiculo' => $vehiculo, 'concepto' => 'TRANSPORTE DE SOYA',
            'monto' => 1000, 'fecha_ingreso' => now()->format('Y-m-d'),
            'id_tramo' => $tramo, 'origen' => '', 'destino' => '',
            'toneladas' => 10, 'estado_factura' => 'PENDIENTE',
        ], 'id_ingreso');

        // Flete huerfano, sin ruta pero con su texto propio.
        $huerfano = \Illuminate\Support\Facades\DB::table('global.ingresos')->insertGetId([
            'id_vehiculo' => $vehiculo, 'concepto' => 'TRANSPORTE DE SOYA',
            'monto' => 1000, 'fecha_ingreso' => now()->format('Y-m-d'),
            'origen' => 'SANTA CRUZ', 'destino' => 'BUENA VISTA',
            'toneladas' => 10, 'estado_factura' => 'PENDIENTE',
        ], 'id_ingreso');

        $this->actingAs($this->usuarioConRol('admin'))->get(
            '/reportes/pdf?fecha_inicio=' . now()->format('Y-m-01') . '&fecha_fin=' . now()->format('Y-m-d')
        )->assertOk();

        $filas = $this->filasDelReporte();

        $delEnlazado = $filas->firstWhere('id', $enlazado);
        $this->assertNotNull($delEnlazado, 'El flete enlazado debe estar en el reporte.');
        $this->assertSame('SANTA CRUZ', $delEnlazado->origen, 'Debe tomar la ruta del catalogo.');
        $this->assertSame('LA PAZ', $delEnlazado->destino);

        $delHuerfano = $filas->firstWhere('id', $huerfano);
        $this->assertNotNull($delHuerfano, 'Un flete sin ruta no puede desaparecer del reporte.');
        $this->assertSame('SANTA CRUZ', $delHuerfano->origen, 'Debe conservar su propio texto.');
        $this->assertSame('BUENA VISTA', $delHuerfano->destino);
    }

    /**
     * Filas que el reporte entrega, tal como las lee la vista.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function filasDelReporte(): \Illuminate\Support\Collection
    {
        $reflexion = new \ReflectionMethod(ReporteController::class, 'getIngresos');
        $reflexion->setAccessible(true);

        // El controller tiene dependencias: se pide al contenedor en vez de
        // instanciarlo a mano.
        $controller = app(ReporteController::class);

        return $reflexion->invoke(
            $controller,
            now()->format('Y-m-01'),
            now()->format('Y-m-d'),
            'TODOS',
            null
        );
    }

    #[Test]
    public function el_helper_de_escape_esta_disponible_para_las_vistas(): void
    {
        $this->seedMinimo();

        $js = file_get_contents(base_path('resources/js/app.js'));

        // Sin `esc()` las tablas seguirian interpolando datos crudos.
        $this->assertStringContainsString('window.esc', $js);
        $this->assertStringContainsString('window.bs', $js);
        $this->assertStringContainsString('window.txt', $js);
    }
}