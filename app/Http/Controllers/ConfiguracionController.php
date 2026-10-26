<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AuditoriaService;
use App\Services\BcbService;
use App\Services\ConfiguracionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CONFIGURACION (exclusiva del administrador).
 *
 * Antes eran dos closures anonimos en routes/web.php que solo exigian
 * `auth`: cualquier operador, e incluso un usuario de solo lectura, podia
 * cambiar el TIPO DE CAMBIO y el PRECIO POR TONELADA, que son los dos
 * parametros de los que dependen todos los calculos financieros.
 *
 * Los parametros financieros viven ahora en ConfiguracionService.
 */
class ConfiguracionController extends Controller
{
    public function __construct(
        private readonly ConfiguracionService $config,
        private readonly AuditoriaService $auditoria,
        private readonly BcbService $bcb,
    ) {}

    public function index(): View
    {
        // Se aseguran las llaves nuevas para que existan aunque la base sea
        // de una instalacion anterior a esta migracion.
        foreach (['tipo_cambio' => '6.96', 'precio_tonelada_usd' => '13', 'valor_flota_por_unidad' => '0', 'saldo_inicial_proveedores' => '0', ConfiguracionService::FUENTE_TIPO_CAMBIO => 'MANUAL'] as $llave => $valor) {
            if (!$this->config->obtener($llave)) {
                \Illuminate\Support\Facades\DB::table('global.configuracion')
                    ->insertOrIgnore(['llave' => $llave, 'valor' => $valor, 'descripcion' => $llave, 'created_at' => now()]);
            }
        }

        $this->config->limpiarCache();

        // El tipo de cambio oficial al dia, para que la sesion lo vea incluso
        // si el BCB tarda. El front lo refresca contra /api/tipo-cambio-bcb.
        $bcb = $this->bcb()->obtener();

        return view('configuracion.index', [
            'config' => $this->config->todo(),
            'tipoCambio' => $this->config->tipoCambio(),
            'precioTonelada' => $this->config->precioTonelada(),
            'valorFlota' => $this->config->numero('valor_flota_por_unidad', 0),
            'fuenteTipoCambio' => $this->config->fuenteTipoCambio(),
            'bcb' => $bcb,
            'ultimoHistorico' => \Illuminate\Support\Facades\DB::table('global.tipo_cambio_historico')
                ->orderByDesc('fecha')
                ->first(['fecha', 'tipo_de_cambio']),
        ]);
    }

    private function bcb(): BcbService
    {
        return $this->bcb;
    }

    /** Valor oficial al dia (con opcion de refresco contra el servidor BCB). */
    public function apiTipoCambioBcb(Request $request): JsonResponse
    {
        $datos = $this->bcb()->obtener($request->boolean('force'));

        if ($datos === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener el tipo de cambio del BCB en este momento. Revisa la conexion e intenta de nuevo.',
            ], 503);
        }

        return response()->json(['success' => true, 'data' => $datos]);
    }

    /**
     * Valor que el sistema usaria para registrar un flete de la fecha que
     * llega por query. La ruta form/edit lo usa para refrescar el campo
     * readonly cuando el usuario cambia la fecha (fuente HISTORICO).
     */
    public function apiTipoCambioPorFecha(Request $request): JsonResponse
    {
        $validado = $request->validate([
            'fecha' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $fecha = $validado['fecha'] ?? null;

        return response()->json([
            'success' => true,
            'data' => [
                'fecha_dia' => \Illuminate\Support\Carbon::parse(
                    ($this->config->fuenteTipoCambio() === 'HISTORICO')
                        ? app(\App\Services\HistoricoTipoCambio::class)->fechaParam($fecha)
                        : ($fecha ?? now())
                )->format('d/m/Y'),
                'valor' => round($this->config->tipoCambioParaFecha($fecha), 2),
                'fuente' => $this->config->fuenteTipoCambio(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validado = $request->validate([
            'tipo_cambio' => ['required', 'numeric', 'gt:0', 'max:100'],
            'precio_tonelada_usd' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'valor_flota_por_unidad' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'saldo_inicial_proveedores' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'fuente_tipo_cambio' => ['nullable', 'string', 'in:MANUAL,BCB,HISTORICO'],
        ]);

        $antes = $this->config->todo();

        // Si el cliente no manda la fuente, se conserva la actual (los POST
        // antiguos solo cambiaban tarifas).
        $fuente = $validado['fuente_tipo_cambio'] ?? $this->config->fuenteTipoCambio();

        $this->config->actualizarVarios([
            ConfiguracionService::TIPO_CAMBIO => (string) $validado['tipo_cambio'],
            ConfiguracionService::PRECIO_TONELADA => (string) $validado['precio_tonelada_usd'],
            'valor_flota_por_unidad' => (string) ($validado['valor_flota_por_unidad'] ?? 0),
            'saldo_inicial_proveedores' => (string) ($validado['saldo_inicial_proveedores'] ?? 0),
            ConfiguracionService::FUENTE_TIPO_CAMBIO => $fuente,
        ]);

        $this->auditoria->registrar('CONFIGURACION_ACTUALIZADA', 'configuracion', 'Parametros financieros actualizados', [
            'antes' => [
                'tipo_cambio' => $antes[ConfiguracionService::TIPO_CAMBIO] ?? null,
                'precio_tonelada_usd' => $antes[ConfiguracionService::PRECIO_TONELADA] ?? null,
                'fuente_tipo_cambio' => $antes[ConfiguracionService::FUENTE_TIPO_CAMBIO] ?? null,
            ],
            'despues' => [
                'tipo_cambio' => $validado['tipo_cambio'],
                'precio_tonelada_usd' => $validado['precio_tonelada_usd'],
                'fuente_tipo_cambio' => $fuente,
            ],
        ]);

        return redirect()->route('configuracion.index')
            ->with('success', 'Configuracion actualizada exitosamente');
    }
}