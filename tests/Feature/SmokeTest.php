<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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