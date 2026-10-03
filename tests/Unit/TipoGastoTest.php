<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Rol;
use App\Enums\TipoGasto;
use App\Enums\EstadoVehiculo;
use App\Enums\CondicionPago;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * El enum TipoGasto es la fuente unica del dominio: la misma lista viaja al
 * CHECK constraint de PostgreSQL, a la validacion de Form Requests y a los
 * filtros de reportes. Si divergen, aparece el bug que existia antes
 * ('Sueldo' en PHP vs 'Sueldos' en la base).
 */
class TipoGastoTest extends TestCase
{
    #[Test]
    public function el_catalogo_usa_los_codigos_en_singular(): void
    {
        // El bug historico fue la coexistencia de 'Sueldo' (PHP) y
        // 'Sueldos' (CHECK). Se comprueban los plurales concretos que
        // no existen en el canonico, no una regla general de terminacion:
        // 'Llantas' y 'Varios' son plurales legitimos en espanol
        // (de "llanta" y de "vario").
        foreach (['Sueldos', 'Viaticos', 'Mantenimientos', 'Peajes', 'Combustibles'] as $plural) {
            $this->assertNotContains($plural, TipoGasto::values());
        }

        $this->assertContains('Sueldo', TipoGasto::values());
        $this->assertContains('Viatico', TipoGasto::values());
    }

    #[Test]
    public function no_hay_codigos_duplicados_en_el_catalogo(): void
    {
        $valores = TipoGasto::values();

        $this->assertSame(
            count($valores),
            count(array_unique($valores)),
            'El catalogo tiene valores repetidos: eso romperia el CHECK y los filtros.'
        );
    }

    #[Test]
    public function el_catalogo_contiene_los_tipos_operativos_esenciales(): void
    {
        $valores = TipoGasto::values();

        foreach (['Combustible', 'Mantenimiento', 'Peaje', 'Sueldo', 'Viatico'] as $tipo) {
            $this->assertContains($tipo, $valores);
        }
    }

    #[Test]
    public function normaliza_los_valores_legacy(): void
    {
        // Estos son los valores que existian en la base antes de la
        // normalizacion y que la validacion PHP nunca aceptaba.
        $casos = [
            'Sueldos' => TipoGasto::Sueldo,
            'Viaticos' => TipoGasto::Viatico,
            'Administracion' => TipoGasto::Administrativo,
            'Compra_Activos' => TipoGasto::CompraActivos,
            'Caja Chica' => TipoGasto::CajaChica,
            'Caja Chiva' => TipoGasto::CajaChica,
            'Seguros' => TipoGasto::Seguro,
            'Servicios Básicos' => TipoGasto::ServiciosBasicos,
            'Combustible' => TipoGasto::Combustible,
        ];

        foreach ($casos as $entrada => $esperado) {
            $this->assertSame(
                $esperado,
                TipoGasto::normalizar($entrada),
                "La entrada «{$entrada}» deberia normalizar a {$esperado->value}."
            );
        }
    }

    #[Test]
    public function normaliza_ignorando_acentos_y_mayusculas(): void
    {
        $this->assertSame(TipoGasto::ServiciosBasicos, TipoGasto::normalizar('servicios basicos'));
        $this->assertSame(TipoGasto::ServiciosBasicos, TipoGasto::normalizar('SERVICIOS BÁSICOS'));
        $this->assertSame(TipoGasto::ServiciosBasicos, TipoGasto::normalizar('ServiciosBasicos'));
    }

    #[Test]
    public function devuelve_null_para_entradas_no_reconocibles(): void
    {
        $this->assertNull(TipoGasto::normalizar(null));
        $this->assertNull(TipoGasto::normalizar(''));
        $this->assertNull(TipoGasto::normalizar('   '));
        $this->assertNull(TipoGasto::normalizar('Tipo Inventado 2099'));
    }

    #[Test]
    public function genera_una_lista_sql_segura_para_el_check_constraint(): void
    {
        $sql = TipoGasto::sqlList();

        foreach (TipoGasto::values() as $valor) {
            $this->assertStringContainsString("'{$valor}'", $sql);
        }

        // Debe ser una lista cerrada y sin comillas sueltas: se inyecta
        // literal dentro de un CHECK constraint.
        $this->assertMatchesRegularExpression(
            "/^('[A-Za-z]+'(, '[A-Za-z]+')*)?$/",
            $sql,
            'La lista SQL no es una enumeracion válida de cadenas.'
        );
    }

    #[Test]
    public function solo_combustible_pide_detalle_de_galones(): void
    {
        $this->assertTrue(TipoGasto::Combustible->requiereDetalleCombustible());

        foreach (TipoGasto::cases() as $caso) {
            if ($caso !== TipoGasto::Combustible) {
                $this->assertFalse($caso->requiereDetalleCombustible(), "{$caso->value} no debe pedir galones.");
            }
        }
    }

    #[Test]
    public function clasifica_los_tipos_por_naturaleza(): void
    {
        $this->assertTrue(TipoGasto::Sueldo->esRemuneracion());
        $this->assertTrue(TipoGasto::Viatico->esRemuneracion());
        $this->assertFalse(TipoGasto::Peaje->esRemuneracion());

        $this->assertTrue(TipoGasto::Peaje->esOperativoDeUnidad());
        $this->assertFalse(TipoGasto::Impuestos->esOperativoDeUnidad());

        $this->assertTrue(TipoGasto::Impuestos->esGeneralPorDefecto());
        $this->assertFalse(TipoGasto::Peaje->esGeneralPorDefecto());
    }

    #[Test]
    public function la_jerarquia_de_roles_es_coherente(): void
    {
        $this->assertGreaterThan(Rol::Supervisor->nivel(), Rol::Admin->nivel());
        $this->assertGreaterThan(Rol::Operador->nivel(), Rol::Supervisor->nivel());
        $this->assertGreaterThan(Rol::Lectura->nivel(), Rol::Operador->nivel());
    }

    #[Test]
    public function las_permisos_de_lectura_son_minimos(): void
    {
        $this->assertFalse(Rol::Lectura->puedeCrear());
        $this->assertFalse(Rol::Lectura->puedeEditar());
        $this->assertFalse(Rol::Lectura->puedeEliminar());
        $this->assertFalse(Rol::Lectura->puedeAnular());
        $this->assertFalse(Rol::Lectura->esAdmin());
        $this->assertTrue(Rol::Lectura->puedeVer());
    }

    #[Test]
    public function un_rol_desconocido_degrada_a_solo_lectura(): void
    {
        // Principio de minimo privilegio: nunca caer en un rol con permisos.
        $this->assertSame(Rol::Lectura, Rol::normalizar('superadmin'));
        $this->assertSame(Rol::Lectura, Rol::normalizar(null));
        $this->assertSame(Rol::Lectura, Rol::normalizar(''));
    }

    #[Test]
    public function el_operador_captura_pero_no_ni_borra_ni_anula(): void
    {
        // En este sistema todo registro se asienta de inmediato: no hay
        // estado "borrador". Por tanto un borrado ES una anulacion
        // contable y le corresponde a un supervisor, no al capturista.
        $this->assertTrue(Rol::Operador->puedeCrear());
        $this->assertTrue(Rol::Operador->puedeEditar());
        $this->assertFalse(Rol::Operador->puedeEliminar());
        $this->assertFalse(Rol::Operador->puedeAnular());
        $this->assertFalse(Rol::Operador->esAdmin());

        $this->assertTrue(Rol::Supervisor->puedeEliminar());
        $this->assertTrue(Rol::Supervisor->puedeAnular());
    }

    #[Test]
    public function los_estados_de_unidad_estan_bien_definidos(): void
    {
        $this->assertTrue(EstadoVehiculo::Activo->esOperativo());
        $this->assertTrue(EstadoVehiculo::Taller->esOperativo());
        $this->assertFalse(EstadoVehiculo::Vendido->esOperativo());

        $this->assertSame(EstadoVehiculo::Activo, EstadoVehiculo::normalizar(1));
        $this->assertSame(EstadoVehiculo::Vendido, EstadoVehiculo::normalizar(3));
        // Valor fuera de rango: degrada a Activo, no a Vendido.
        $this->assertSame(EstadoVehiculo::Activo, EstadoVehiculo::normalizar(99));
        $this->assertSame(EstadoVehiculo::Activo, EstadoVehiculo::normalizar(null));
    }

    #[Test]
    public function solo_credito_es_credito(): void
    {
        $this->assertTrue(CondicionPago::Credito->esCredito());
        $this->assertFalse(CondicionPago::Contado->esCredito());
        $this->assertSame(['CONTADO', 'CREDITO'], CondicionPago::values());
    }
}