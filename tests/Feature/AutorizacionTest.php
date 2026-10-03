<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TipoGasto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * CONTROL DE ACCESO POR ROL
 *
 * Este es el agujero de seguridad mas grave que se corrigio. La aplicacion
 * DEFINIA cuatro roles (admin, supervisor, operador, lectura) pero solo
 * `admin` estaba protegido por un middleware. Los otros tres no tenían
 * ninguna comprobacion: un usuario de rol `lectura` podía crear, editar y
 * borrar gastos, fletes, inventario, bancos y proveedores. El menu ocultaba
 * enlaces, pero eso es presentacion.
 *
 * Matriz implementada en App\Policies\ModuloPolicy:
 *
 *                  ver  crear  editar  eliminar  anular  administrar
 *   admin           si    si     si       si       si       si
 *   supervisor      si    si     si       si       si       no
 *   operador        si    si     si       no       no       no
 *   lectura         si    no     no       no       no       no
 *
 * @uses \App\Policies\ModuloPolicy
 * @uses \App\Http\Middleware\CapacidadMiddleware
 */
class AutorizacionTest extends TestCase
{
    use RefreshDatabase;

    // =================================================================
    // IDENTIFICADOR DEL USUARIO
    //
    // `usuarios` tiene dos columnas distintas: `usuario` (el nombre de
    // login) e `id_usuario` (la clave primaria). Laravel usa por defecto
    // `username` para identificar, que en esta tabla no existe, y al
    // definirse como `usuario` arrastra el error al resto del codigo:
    // `getAuthIdentifier()` pasa a devolver texto donde se esperaba el id.
    // =================================================================

    #[Test]
    public function la_columna_de_identificacion_es_usuario_y_no_una_inexistente(): void
    {
        $usuario = $this->usuarioConRol('admin');

        $this->assertSame('usuario', $usuario->getAuthIdentifierName());
        $this->assertNotSame('username', $usuario->getAuthIdentifierName());
    }

    #[Test]
    public function el_identificador_y_la_clave_primaria_son_distintos(): void
    {
        $usuario = $this->usuarioConRol('operador');

        // `getAuthIdentifier()` devuelve el nombre de login...
        $this->assertSame($usuario->usuario, $usuario->getAuthIdentifier());

        // ...y el id real, que es el que va a las columnas *_id / *_por.
        $this->assertSame($usuario->id_usuario, $usuario->getKey());
        $this->assertIsInt($usuario->getKey());

        // Mezclarlos hacia que un UPDATE comparara texto contra un integer.
        $this->assertNotSame($usuario->getKey(), $usuario->getAuthIdentifier());
    }

    #[Test]
    public function iniciar_sesion_guarda_la_sesion_y_registra_el_acceso(): void
    {
        $creado = DB::table('global.usuarios')->insertGetId([
            'usuario' => 'login_test',
            'email' => 'login_test@test.local',
            'contrasenha' => bcrypt('secreto123'),
            'nombres' => 'Login',
            'apellidos' => 'Test',
            'rol' => 'supervisor',
            'estado' => 1,
            'fecha_creacion' => now(),
        ], 'id_usuario');

        $respuesta = $this->post('/login', [
            'username' => 'login_test',
            'password' => 'secreto123',
        ]);

        $respuesta->assertRedirect();

        $this->assertAuthenticated();

        // `auth()->id()` devuelve lo que declara `getAuthIdentifierName()`,
        // que aqui es `usuario`. Es el comportamiento de Laravel y no un
        // fallo, pero explica por que el codigo de aplicacion usa
        // `auth()->user()->getKey()` para las columnas *_id: son cosas
        // distintas y mezclarlas hacia que un UPDATE comparara texto
        // contra un integer.
        $this->assertSame('login_test', auth()->id());
        $this->assertSame($creado, auth()->user()->getKey());

        $registro = DB::table('global.usuarios')->where('id_usuario', $creado)->first();

        $this->assertNotNull($registro->ultimo_login, 'El acceso debe quedar auditado.');
    }

    #[Test]
    public function un_nombre_de_usuario_inexistente_no_produce_error_de_servidor(): void
    {
        // Con `username` hardcodeado, este caso reventaba con 500 y el
        // mensaje "no existe la columna username".
        $respuesta = $this->post('/login', [
            'username' => 'no_existe_este_usuario',
            'password' => 'lo_que_sea',
        ]);

        $respuesta->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /** @return array<string, mixed> */
    private function datosGasto(): array
    {
        return [
            'id_vehiculo' => $this->vehiculo(),
            'id_clasificador' => $this->clasificadorDeUnidad(TipoGasto::Peaje->value),
            'monto' => 250.00,
            'fecha_gasto' => now()->format('Y-m-d'),
            'concepto' => 'Peaje de prueba',
            'condicion_pago' => 'CONTADO',
            'id_banco' => $this->banco(),
        ];
    }

    // =================================================================
    // LECTURA: NO PUEDE ESCRIBIR NADA
    // =================================================================

    #[Test]
    public function el_rol_lectura_no_registra_gastos(): void
    {
        $this->actingAs($this->usuarioConRol('lectura'))
            ->post('/gastos', $this->datosGasto())
            ->assertForbidden();

        $this->assertDatabaseCount('global.gastos', 0);
    }

    #[Test]
    public function el_rol_lectura_no_registra_fletes(): void
    {
        $vehiculo = $this->vehiculo();

        $this->actingAs($this->usuarioConRol('lectura'))
            ->post('/facturacion', [
                'id_vehiculo' => $vehiculo,
                'monto' => 3500.00,
                'fecha_ingreso' => now()->format('Y-m-d'),
                'concepto' => 'Flete',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('global.ingresos', 0);
    }

    #[Test]
    public function el_rol_lectura_no_crea_maestros(): void
    {
        $lectura = $this->usuarioConRol('lectura');

        $this->actingAs($lectura)->post('/vehiculos', [
            'placa_vehiculo' => 'ABC-123',
            'tipo_vehiculo' => 'CAMION',
            'estado' => 1,
        ])->assertForbidden();

        $this->actingAs($lectura)->post('/proveedores', [
            'nombre_proveedor' => 'Intruso',
        ])->assertForbidden();

        $this->actingAs($lectura)->post('/bancos', [
            'nombre_banco' => 'Intruso',
            'numero_cuenta' => '1',
            'titular' => 'X',
            'tipo_cuenta' => 'AHORROS',
            'moneda' => 'BOB',
            'saldo_inicial' => 0,
        ])->assertForbidden();

        $this->assertDatabaseCount('global.vehiculos', 0);
        $this->assertDatabaseCount('global.proveedores', 0);
        $this->assertDatabaseCount('global.bancos', 0);
    }

    #[Test]
    public function el_rol_lectura_no_toca_el_almacen(): void
    {
        $this->actingAs($this->usuarioConRol('lectura'))
            ->post('/items', [
                'nombre_producto' => 'Intruso',
                'unidad_medida' => 'UNIDAD',
            ])
            ->assertForbidden();

        $this->actingAs($this->usuarioConRol('lectura'))
            ->post('/grupos', ['nombre' => 'Intruso'])
            ->assertForbidden();

        $this->assertDatabaseCount('global.inventario', 0);
    }

    // =================================================================
    // OPERADOR: OPERA PERO NO ANULA NI BORRA
    // =================================================================

    #[Test]
    public function el_operador_registra_gastos(): void
    {
        $this->actingAs($this->usuarioConRol('operador'))
            ->post('/gastos', $this->datosGasto())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('global.gastos', 1);
    }

    #[Test]
    public function el_operador_no_anula_fletes(): void
    {
        $vehiculo = $this->vehiculo();
        $fleteId = DB::table('global.ingresos')->insertGetId([
            'id_vehiculo' => $vehiculo,
            'monto' => 1000,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'concepto' => 'Flete',
            'nro_documento' => 'I_99999',
            'estado_factura' => 'PENDIENTE',
        ], 'id_ingreso');

        $this->actingAs($this->usuarioConRol('operador'))
            ->delete('/facturacion/' . $fleteId)
            ->assertForbidden();

        $this->assertSame('PENDIENTE', DB::table('global.ingresos')->where('id_ingreso', $fleteId)->value('estado_factura'));
    }

    #[Test]
    public function el_operador_no_da_de_baja_unidades(): void
    {
        $vehiculo = $this->vehiculo();

        $this->actingAs($this->usuarioConRol('operador'))
            ->post('/api/vehiculos/vender', ['id_vehiculo' => $vehiculo])
            ->assertForbidden();

        $this->assertSame(1, (int) DB::table('global.vehiculos')->where('id_vehiculo', $vehiculo)->value('estado'));
    }

    #[Test]
    public function el_supervisor_si_puede_anular_y_dar_de_baja(): void
    {
        $vehiculo = $this->vehiculo();
        $supervisor = $this->usuarioConRol('supervisor');

        $this->actingAs($supervisor)
            ->post('/api/vehiculos/vender', ['id_vehiculo' => $vehiculo])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(3, (int) DB::table('global.vehiculos')->where('id_vehiculo', $vehiculo)->value('estado'));
    }

    // =================================================================
    // ADMINISTRACION
    // =================================================================

    #[Test]
    public function solo_admin_entra_a_usuarios(): void
    {
        foreach (['supervisor', 'operador', 'lectura'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))->get('/usuarios')->assertForbidden();
        }

        $this->actingAs($this->usuarioConRol('admin'))->get('/usuarios')->assertOk();
    }

    #[Test]
    public function solo_admin_lista_usuarios_por_api(): void
    {
        // Antes, `api/usuarios` solo exigia autenticacion.
        foreach (['supervisor', 'operador', 'lectura'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->getJson('/api/usuarios')
                ->assertForbidden();
        }

        $this->actingAs($this->usuarioConRol('admin'))
            ->getJson('/api/usuarios')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    #[Test]
    public function solo_admin_cambia_los_parametros_financieros(): void
    {
        $datos = [
            'tipo_cambio' => 6.96,
            'precio_tonelada_usd' => 13,
        ];

        // El tipo de cambio y el precio por tonelada gobiernan todos los
        // calculos: permitirlo a un operador era un fallo de integridad.
        foreach (['supervisor', 'operador', 'lectura'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->post('/configuracion', $datos)
                ->assertForbidden();
        }

        $this->actingAs($this->usuarioConRol('admin'))
            ->post('/configuracion', $datos)
            ->assertSessionHasNoErrors();

        $this->assertSame('6.96', DB::table('global.configuracion')->where('llave', 'tipo_cambio')->value('valor'));
    }

    // =================================================================
    // LECTURA DE CATALOGOS
    // =================================================================

    #[Test]
    public function cualquier_rol_autenticado_puede_consultar_los_catalogos(): void
    {
        foreach (['admin', 'supervisor', 'operador', 'lectura'] as $rol) {
            $usuario = $this->usuarioConRol($rol);

            foreach (['/vehiculos', '/personal', '/almacen', '/items', '/grupos',
                      '/tramos', '/facturacion', '/bancos', '/proveedores',
                      '/reportes', '/gastos-generales', '/clasificadores'] as $ruta) {
                $this->actingAs($usuario)->get($ruta)->assertOk();
            }
        }
    }

    #[Test]
    public function el_rol_lectura_sigue_viendo_el_panel(): void
    {
        $this->actingAs($this->usuarioConRol('lectura'))->get('/dashboard')->assertOk();
        $this->actingAs($this->usuarioConRol('lectura'))->get('/documentos')->assertOk();
        $this->actingAs($this->usuarioConRol('lectura'))->get('/')->assertOk();
    }

    // =================================================================
    // VISITANTE
    // =================================================================

    #[Test]
    public function un_visitante_es_redirigido_al_login_en_todas_las_rutas(): void
    {
        foreach (['/dashboard', '/gastos', '/gastos-generales', '/clasificadores',
                  '/facturacion', '/almacen', '/bancos', '/proveedores',
                  '/reportes', '/usuarios', '/configuracion'] as $ruta) {
            $this->get($ruta)->assertRedirect(route('login'));
        }
    }

    #[Test]
    public function un_visitante_no_alcanza_la_api(): void
    {
        $this->getJson('/api/vehiculos')->assertUnauthorized();
        $this->getJson('/api/clasificadores')->assertUnauthorized();
    }

    // =================================================================
    // MENU COHERENTE CON EL ROL
    // =================================================================

    #[Test]
    public function el_menu_oculta_lo_que_el_rol_no_puede_hacer(): void
    {
        $contenido = $this->actingAs($this->usuarioConRol('lectura'))->get('/dashboard')->getContent();

        // El menu se construye por capacidad, no por una condicion suelta.
        $this->assertStringNotContainsString(route('usuarios.index'), $contenido);
        $this->assertStringNotContainsString(route('configuracion.index'), $contenido);

        $contenidoAdmin = $this->actingAs($this->usuarioConRol('admin'))->get('/dashboard')->getContent();

        $this->assertStringContainsString(route('usuarios.index'), $contenidoAdmin);
        $this->assertStringContainsString(route('configuracion.index'), $contenidoAdmin);
    }
}