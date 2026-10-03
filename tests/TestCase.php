<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // La conexion declara `search_path = global`, pero en una base recién
        // creada ese esquema no existe. Laravel crea la tabla de registro de
        // migraciones ANTES de correr la migracion que crea el esquema, y
        // falla con "Invalid schema name".
        //
        // Se resuelve aqui porque es el unico punto que precede a la cadena
        // de migraciones. La aplicacion debe existir para poder hablar con la
        // base, de ahi el createApplication() previo.
        $this->createApplication();

        try {
            \Illuminate\Support\Facades\DB::statement('CREATE SCHEMA IF NOT EXISTS global');
        } catch (\Throwable) {
            // Si no se puede crear (permisos, motor no Postgres), el fallo
            // aparecera despues con un mensaje mas claro que este.
        }

        parent::setUp();
    }

    /**
     * Usuario de prueba con el rol indicado.
     *
     * Se inserta directo en `global.usuarios` porque el modelo User mapea esa
     * tabla con clave primaria `id_usuario` y sin timestamps.
     */
    protected function usuarioConRol(string $rol, array $atributos = []): \App\Models\User
    {
        $usuario = 'test_' . $rol . '_' . uniqid();

        $id = \Illuminate\Support\Facades\DB::table('global.usuarios')->insertGetId([
            'usuario' => $usuario,
            'email' => $usuario . '@test.local',
            'contrasenha' => bcrypt('secreto123'),
            'nombres' => 'Usuario',
            'apellidos' => ucfirst($rol),
            'rol' => $rol,
            'estado' => 1,
            'fecha_creacion' => now(),
        ], 'id_usuario');

        return \App\Models\User::find($id) ?? tap(new \App\Models\User())->forceFill(array_merge([
            'id_usuario' => $id,
            'usuario' => $usuario,
            'rol' => $rol,
        ], $atributos));
    }

    /** Clasificador de gasto de prueba. Devuelve el id insertado. */
    protected function clasificador(array $atributos = []): int
    {
        static $n = 0;
        $n++;

        // `codigo` es NOT NULL sin default en la base, asi que el fixture
        // debe enviarlo. Usa la misma correlatividad del servicio en vez de
        // inventar un código propio: si no, `siguienteCodigo()` calcularía un
        // número distinto al real y los tests de secuencia no probarían nada.
        $siguiente = app(\App\Services\ClasificadorGastoService::class)->siguienteCodigo();

        return \Illuminate\Support\Facades\DB::table('global.clasificador_gastos')->insertGetId(array_merge([
            'codigo' => $siguiente,
            'descripcion' => 'Clasificador de prueba ' . $n,
            'tipo_gasto' => 'Varios',
            'afecta_unidad' => true,
            'afecta_general' => false,
            'estado' => 'ACTIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ], $atributos), 'id_clasificador');
    }

    /** Unidad de prueba en estado activo. Devuelve el id insertado. */
    protected function vehiculo(array $atributos = []): int
    {
        static $contador = 0;
        $contador++;

        return \Illuminate\Support\Facades\DB::table('global.vehiculos')->insertGetId(array_merge([
            'placa_vehiculo' => 'TEST-' . str_pad((string) $contador, 4, '0', STR_PAD_LEFT),
            'tipo_vehiculo' => 'VOLQUETE',
            'marca' => 'Test',
            'modelo' => 'X1',
            'estado' => 1,
            'capacidad' => 10,
            'kilometraje' => 0,
        ], $atributos), 'id_vehiculo');
    }

    /** Cuenta bancaria de prueba. Devuelve el id insertado. */
    protected function banco(array $atributos = []): int
    {
        static $contador = 0;
        $contador++;

        return \Illuminate\Support\Facades\DB::table('global.bancos')->insertGetId(array_merge([
            'nombre_banco' => 'Banco Test ' . $contador,
            'numero_cuenta' => (string) (1000000000 + $contador),
            'titular' => 'DS TRANSPORTE',
            'tipo_cuenta' => 'AHORROS',
            'moneda' => 'BOB',
            'saldo_inicial' => 10000,
            'saldo_actual' => 10000,
            'estado' => 'ACTIVO',
        ], $atributos), 'id_banco');
    }

    /** Proveedor de prueba. Devuelve el id insertado. */
    protected function proveedor(array $atributos = []): int
    {
        static $contador = 0;
        $contador++;

        return \Illuminate\Support\Facades\DB::table('global.proveedores')->insertGetId(array_merge([
            'nombre_proveedor' => 'Proveedor Test ' . $contador,
            'tipo_proveedor' => 'GENERAL',
            'estado' => 1,
        ], $atributos), 'id_proveedor');
    }

    /** Producto de inventario con stock inicial. Devuelve el id insertado. */
    protected function producto(float $stock = 10, array $atributos = []): int
    {
        static $contador = 0;
        $contador++;

        return \Illuminate\Support\Facades\DB::table('global.inventario')->insertGetId(array_merge([
            'codigo' => 'TS-' . str_pad((string) $contador, 3, '0', STR_PAD_LEFT) . '-00001',
            'nombre_producto' => 'Producto Test ' . $contador,
            'unidad_medida' => 'UNIDAD',
            'stock_actual' => $stock,
            'stock_minimo' => 1,
            'precio_compra' => 10,
            'ultimo_costo' => 10,
            'estado' => 'ACTIVO',
        ], $atributos), 'id_inventario');
    }

    /** Personal de prueba. Devuelve el id insertado. */
    protected function personal(array $atributos = []): int
    {
        static $contador = 0;
        $contador++;

        return \Illuminate\Support\Facades\DB::table('global.personal')->insertGetId(array_merge([
            'nombres' => 'Conductor',
            'apellidos' => 'Prueba ' . $contador,
            'ci' => 'TEST' . str_pad((string) $contador, 6, '0', STR_PAD_LEFT),
            'cargo' => 'CONDUCTOR',
            'estado' => 1,
        ], $atributos), 'id_personal');
    }

    /** Id del clasificador activo de alcance `unidad` para el tipo dado. */
    protected function clasificadorDeUnidad(string $tipo = 'Varios'): int
    {
        $existente = \Illuminate\Support\Facades\DB::table('global.clasificador_gastos')
            ->where('tipo_gasto', $tipo)
            ->where('afecta_unidad', true)
            ->where('estado', 'ACTIVO')
            ->value('id_clasificador');

        return $existente ?: $this->clasificador(['tipo_gasto' => $tipo, 'afecta_unidad' => true]);
    }

    /** Id del clasificador activo de alcance `general` para el tipo dado. */
    protected function clasificadorGeneral(string $tipo = 'Varios'): int
    {
        $existente = \Illuminate\Support\Facades\DB::table('global.clasificador_gastos')
            ->where('tipo_gasto', $tipo)
            ->where('afecta_general', true)
            ->where('estado', 'ACTIVO')
            ->value('id_clasificador');

        return $existente ?: $this->clasificador([
            'tipo_gasto' => $tipo,
            'afecta_unidad' => false,
            'afecta_general' => true,
        ]);
    }
}