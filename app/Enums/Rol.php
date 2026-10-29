<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Roles del sistema. Refleja el CHECK constraint usuarios_rol_check.
 *
 * Matriz de permisos (App\Policies\ModuloPolicy + modulosDeLectura()):
 *
 *                  ver        crear  editar  eliminar  anular  administrar
 *   admin          todo         si     si       si       si        si
 *   supervisor     todo         si     si       no       si        no
 *   operador       todo         si     si       no       no        no
 *   lectura        limitada     no     no       no       no        no
 *
 * El ver de lectura queda restringido (ModuloPolicy::SOLO_CONSULTA):
 * reportes (visualizar y descargar), informacion de almacen y la
 * portada/perfil. Nigun otro modulo.
 */
enum Rol: string
{
    case Admin = 'admin';
    case Supervisor = 'supervisor';
    case Operador = 'operador';
    case Lectura = 'lectura';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }

    public static function sqlList(): string
    {
        return implode(', ', array_map(
            static fn (string $v): string => "'" . $v . "'",
            self::values()
        ));
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Supervisor => 'Supervisor',
            self::Operador => 'Operador',
            self::Lectura => 'Solo Lectura',
        };
    }

    /** Puede ver el sistema. */
    public function puedeVer(): bool
    {
        return true;
    }

    /** Puede crear registros (fletes, gastos, movimientos, maestros). */
    public function puedeCrear(): bool
    {
        return $this !== self::Lectura;
    }

    /** Puede modificar registros existentes. */
    public function puedeEditar(): bool
    {
        return in_array($this, [self::Admin, self::Supervisor, self::Operador], true);
    }

    /** Puede eliminar registros de forma definitiva (baja de maestros). */
    public function puedeEliminar(): bool
    {
        // SOLO administrador: un borrado asienta en el ledger y es una
        // decision contable final. El supervisor revisa y aprueba
        // (anular/emitir baja de operacion), pero no elimina registros.
        return $this === self::Admin;
    }

    /** Solo admin: usuarios, configuracion financiera y clasificador maestro. */
    public function esAdmin(): bool
    {
        return $this === self::Admin;
    }

    /** Puede anular documentos ya emitidos (fletes, facturas, gastos). */
    public function puedeAnular(): bool
    {
        return in_array($this, [self::Admin, self::Supervisor], true);
    }

    /** Orden de prioridad (mayor = mas permisos). */
    public function nivel(): int
    {
        return match ($this) {
            self::Admin => 40,
            self::Supervisor => 30,
            self::Operador => 20,
            self::Lectura => 10,
        };
    }

    public static function normalizar(?string $valor): self
    {
        return self::tryFrom(trim((string) $valor)) ?? self::Lectura;
    }
}
