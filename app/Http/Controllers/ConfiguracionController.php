<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AuditoriaService;
use App\Services\ConfiguracionService;
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
    ) {}

    public function index(): View
    {
        // Se aseguran las llaves nuevas para que existan aunque la base sea
        // de una instalacion anterior a esta migracion.
        foreach (['tipo_cambio' => '6.96', 'precio_tonelada_usd' => '13', 'valor_flota_por_unidad' => '0', 'saldo_inicial_proveedores' => '0'] as $llave => $valor) {
            if (!$this->config->obtener($llave)) {
                \Illuminate\Support\Facades\DB::table('global.configuracion')
                    ->insertOrIgnore(['llave' => $llave, 'valor' => $valor, 'descripcion' => $llave, 'created_at' => now()]);
            }
        }

        $this->config->limpiarCache();

        return view('configuracion.index', [
            'config' => $this->config->todo(),
            'tipoCambio' => $this->config->tipoCambio(),
            'precioTonelada' => $this->config->precioTonelada(),
            'valorFlota' => $this->config->numero('valor_flota_por_unidad', 0),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validado = $request->validate([
            'tipo_cambio' => ['required', 'numeric', 'gt:0', 'max:100'],
            'precio_tonelada_usd' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'valor_flota_por_unidad' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'saldo_inicial_proveedores' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ]);

        $antes = $this->config->todo();

        $this->config->actualizarVarios([
            ConfiguracionService::TIPO_CAMBIO => (string) $validado['tipo_cambio'],
            ConfiguracionService::PRECIO_TONELADA => (string) $validado['precio_tonelada_usd'],
            'valor_flota_por_unidad' => (string) ($validado['valor_flota_por_unidad'] ?? 0),
            'saldo_inicial_proveedores' => (string) ($validado['saldo_inicial_proveedores'] ?? 0),
        ]);

        $this->auditoria->registrar('CONFIGURACION_ACTUALIZADA', 'configuracion', 'Parametros financieros actualizados', [
            'antes' => [
                'tipo_cambio' => $antes[ConfiguracionService::TIPO_CAMBIO] ?? null,
                'precio_tonelada_usd' => $antes[ConfiguracionService::PRECIO_TONELADA] ?? null,
            ],
            'despues' => [
                'tipo_cambio' => $validado['tipo_cambio'],
                'precio_tonelada_usd' => $validado['precio_tonelada_usd'],
            ],
        ]);

        return redirect()->route('configuracion.index')
            ->with('success', 'Configuracion actualizada exitosamente');
    }
}