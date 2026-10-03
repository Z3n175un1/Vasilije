<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Rol;
use App\Models\User;

/**
 * Politica unica para todos los modulos de negocio.
 *
 * ANTES
 * -----
 * Existian 4 roles en la BD pero solo `admin` estaba protegido: un
 * middleware en /usuarios y dos `if` sueltos dentro de AlmacenController.
 * Un usuario con rol `lectura` podia crear, editar y borrar gastos, fletes,
 * inventario, bancos, proveedores y personal. El menu ocultaba enlaces,
 * pero eso es presentacion, no seguridad.
 *
 * AHORA
 * -----
 * La matriz de permisos vive en el enum Rol y se consulta por capacidad,
 * de modo que anadir un modulo es registrarlo en UN lugar.
 *
 * Capacidades: ver, crear, editar, eliminar, anular, administrar.
 */
final class ModuloPolicy
{
    /**
     * Modulos que solo el administrador puede tocar.
     *
     * @var list<string>
     */
    private const EXCLUSIVOS_ADMIN = [
        'usuarios',
        'configuracion',
        'clasificadores',
    ];

    public function ver(User $user, string $modulo): bool
    {
        return $this->rol($user)->puedeVer();
    }

    public function crear(User $user, string $modulo): bool
    {
        $rol = $this->rol($user);

        if ($this->esExclusivo($modulo)) {
            return $rol->esAdmin();
        }

        return $rol->puedeCrear();
    }

    public function editar(User $user, string $modulo): bool
    {
        $rol = $this->rol($user);

        if ($this->esExclusivo($modulo)) {
            return $rol->esAdmin();
        }

        return $rol->puedeEditar();
    }

    /**
     * Borrar o dar de baja.
     *
     * Decisión de diseño: el operador captura y corrige, pero no elimina.
     * En este sistema todo registro se asienta de inmediato en el ledger
     * (no hay estado "borrador"), de modo que un borrado es siempre una
     * anulacion contable y le corresponde a un supervisor.
     */
    public function eliminar(User $user, string $modulo): bool
    {
        $rol = $this->rol($user);

        if ($this->esExclusivo($modulo)) {
            return $rol->esAdmin();
        }

        return $rol->puedeEliminar();
    }

    /** Anular documentos ya emitidos (flete, factura). */
    public function anular(User $user, string $modulo): bool
    {
        return $this->rol($user)->puedeAnular();
    }

    /** Configuracion financiera, usuarios, clasificador maestro. */
    public function administrar(User $user, string $modulo): bool
    {
        return $this->rol($user)->esAdmin();
    }

    private function esExclusivo(string $modulo): bool
    {
        return in_array($modulo, self::EXCLUSIVOS_ADMIN, true);
    }

    private function rol(User $user): Rol
    {
        return Rol::normalizar($user->rol);
    }
}
