<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTramoRequest;
use App\Services\AuditoriaService;
use App\Services\ConfiguracionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** RUTAS origen -> destino con su tarifado. */
class TramoController extends Controller
{
    public function __construct(
        private readonly ConfiguracionService $config,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(): View
    {
        return view('tramos.index');
    }

    public function create(): View
    {
        return view('tramos.form', [
            'tramo' => null,
            'precioTonUsd' => $this->config->precioTonelada(),
            'tipoCambio' => $this->config->tipoCambio(),
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $tramo = DB::table('global.tramos')->where('id_tramo', $id)->first();

        if (!$tramo) {
            return redirect()->route('tramos.index')->with('error', 'Tramo no encontrado.');
        }

        return view('tramos.form', [
            'tramo' => $tramo,
            'precioTonUsd' => $this->config->precioTonelada(),
            'tipoCambio' => $this->config->tipoCambio(),
        ]);
    }

    public function store(StoreTramoRequest $request): RedirectResponse
    {
        $id = DB::table('global.tramos')->insertGetId($request->datosNormalizados(), 'id_tramo');

        $this->auditoria->registrar('TRAMO_CREADO', 'tramos', "Ruta #{$id} creada", ['id' => $id]);

        return redirect()->route('tramos.index')->with('success', 'Tramo registrado exitosamente');
    }

    public function update(StoreTramoRequest $request, string $id): RedirectResponse
    {
        DB::table('global.tramos')->where('id_tramo', $id)->update($request->datosNormalizados());

        $this->auditoria->registrar('TRAMO_ACTUALIZADO', 'tramos', "Ruta #{$id} actualizada", ['id' => $id]);

        return redirect()->route('tramos.index')->with('success', 'Tramo actualizado exitosamente');
    }

    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        if (!$request->user()?->can('eliminar', 'tramos')) {
            abort(403, 'Su rol no permite eliminar rutas.');
        }

        // Un tramo es un dato tarifario de referencia, no un documento con
        // valor contable: los fletes guardan origen/destino como texto. Se
        // puede eliminar aunque este en uso.
        DB::table('global.tramos')->where('id_tramo', $id)->delete();

        $this->auditoria->registrar('TRAMO_ELIMINADO', 'tramos', "Ruta #{$id} eliminada", ['id' => $id]);

        return $this->respuesta($request, 'Ruta eliminada');
    }

    public function apiList(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.tramos')->orderBy('origen')->get(),
        ]);
    }

    public function apiShow(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.tramos')->where('id_tramo', $id)->first(),
        ]);
    }

    private function respuesta(Request $request, string $mensaje): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $mensaje]);
        }

        return redirect()->route('tramos.index')->with('success', $mensaje);
    }
}