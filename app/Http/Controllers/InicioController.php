<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Rol;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * INICIO ("/documentos"): vista rapida personal, no un resumen del negocio.
 *
 * El usuario pide un "mi perfil" con las ACCIONES QUE EL MISMO realizo:
 * los totales de ingresos y el detalle economico quedan en Reportes, al
 * que solo acceden los roles autorizados. Todo lo que esta vista muestra
 * es propio del usuario: su ficha, su bitacora y sus accesos.
 */
class InicioController extends Controller
{
    /** Cuantos eventos de bitacora propia se muestran. */
    private const EVENTOS_RECIENTES = 10;

    /** Ventana (dias) de los contadores "por mi". */
    private const DIAS_SEMANA = 7;

    public function index(): View
    {
        $usuario = auth()->user();

        return view('documentos.index', [
            'rol' => Rol::normalizar($usuario?->rol),
            'ultimoAcceso' => $this->ultimoAcceso((int) $usuario->getKey()),
            'accionesRecientes' => $this->accionesRecientes((int) $usuario->getKey()),
            'resumenSemana' => $this->resumenSemana((int) $usuario->getKey()),
            // Conteos operativos de toda la empresa: sin montos (los
            // numeros de dinero no son de esta vista).
            'operacion' => $this->operacion(),
            // Solo fechas y conceptos, sin montos.
            'vencimientos' => $this->vencimientos(),
            // Bancos y Configuracion son catalogos financieros: como el
            // resto del sistema, solo el administrador los ve desde aqui.
            'puedeVerFinanzas' => Rol::normalizar($usuario?->rol)->esAdmin(),
        ]);
    }

    /** Fichas del usuario en logs: bitacora propia, ultimas ~10. */
    private function accionesRecientes(int $idUsuario): array
    {
        return DB::table('global.logs_actividad')
            ->where('id_usuario', $idUsuario)
            ->orderByDesc('fecha_evento')
            ->orderByDesc('id_log') // los eventos de UN request comparten fecha
            ->limit(self::EVENTOS_RECIENTES)
            ->get()
            ->each(fn ($e) => $e->datos = json_decode((string) $e->datos_adicionales, true))
            ->all();
    }

    /** El LOGIN_EXITOSO anterior a la sesion actual (si la hay). */
    private function ultimoAcceso(int $idUsuario): ?object
    {
        return DB::table('global.logs_actividad')
            ->where('id_usuario', $idUsuario)
            ->where('accion', 'LOGIN_EXITOSO')
            ->orderByDesc('fecha_evento')
            ->skip(1) // el primero es el login de la sesion en curso
            ->first();
    }

    /** Contadores del propio usuario en la ventana de una semana. */
    private function resumenSemana(int $idUsuario): array
    {
        $desde = now()->subDays(self::DIAS_SEMANA);

        $base = DB::table('global.logs_actividad')
            ->where('id_usuario', $idUsuario)
            ->where('fecha_evento', '>=', $desde);

        $porAccion = fn (string $prefijo) => (clone $base)
            ->where('accion', 'like', $prefijo . '%')
            ->count();

        return [
            'fletes' => $porAccion('FLETE_') + $porAccion('LOTE_'),
            'gastos' => $porAccion('GASTO_'),
            'almacen' => $porAccion('MOVIMIENTO_') + $porAccion('PRODUCTO_'),
            'total' => (clone $base)->count(),
        ];
    }

    /**
     * Conteo operativo global (sin montos): estado de la flota,
     * personal activo y stock bajo de almacén.
     *
     * @return array<string, mixed>
     */
    private function operacion(): array
    {
        return [
            'flota' => [
                'activas' => DB::table('global.vehiculos')->where('estado', 1)->count(),
                'mantenimiento' => DB::table('global.vehiculos')->where('estado', 2)->count(),
                'vendidas' => DB::table('global.vehiculos')->where('estado', 3)->count(),
            ],
            'personalActivo' => DB::table('global.personal')->where('estado', 1)->count(),
            'stockBajo' => DB::table('global.inventario')
                ->where('estado', 'ACTIVO')
                ->whereColumn('stock_actual', '<=', 'stock_minimo')
                ->count(),
        ];
    }

    /**
     * Vencimientos próximos (solo fechas y descripciones):
     *   - Cobros a credito que vencen (fecha posible pago).
     *   - Inventario con fecha de vencimiento cercana.
     *
     * @return array<string, mixed>
     */
    private function vencimientos(): array
    {
        $hoy = now()->toDateString();
        $enUnaSemana = now()->addDays(7)->toDateString();

        return [
            'cobros' => DB::table('global.ingresos')
                ->whereNotNull('fecha_vencimiento')
                ->where('fecha_vencimiento', '>=', $hoy)
                ->where('fecha_vencimiento', '<=', $enUnaSemana)
                ->orderBy('fecha_vencimiento')
                ->limit(5)
                ->get(['id_ingreso', 'fecha_vencimiento', 'concepto', 'cliente_nombre']),
            'productos' => DB::table('global.inventario')
                ->where('estado', 'ACTIVO')
                ->whereNotNull('fecha_vencimiento')
                ->where('fecha_vencimiento', '>=', $hoy)
                ->where('fecha_vencimiento', '<=', now()->addDays(30)->toDateString())
                ->orderBy('fecha_vencimiento')
                ->limit(5)
                ->get(['id_inventario', 'fecha_vencimiento', 'nombre_producto', 'stock_actual']),
        ];
    }
}
