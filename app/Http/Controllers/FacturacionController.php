<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacturacionController extends Controller
{
    public function index()
    {
        return view('facturacion.index');
    }

    public function create()
    {
        $vehiculos = DB::table('global.vehiculos')->where('estado', 1)->orderBy('placa_vehiculo')->get();
        $personal = DB::table('global.personal')->where('estado', 1)
            ->select('global.personal.*', DB::raw("CONCAT(global.personal.nombres, ' ', global.personal.apellidos) as nombre_completo"))
            ->orderBy('nombres')->get();
        $tramos = DB::table('global.tramos')->orderBy('origen')->get();
        return view('facturacion.form', ['ingreso' => null, 'vehiculos' => $vehiculos, 'personal' => $personal, 'tramos' => $tramos]);
    }

    public function edit($id)
    {
        $ingreso = DB::table('global.ingresos')->where('id_ingreso', $id)->first();
        if (!$ingreso) return redirect()->route('facturacion.index')->with('error', 'Registro no encontrado');
        $vehiculos = DB::table('global.vehiculos')->orderBy('placa_vehiculo')->get();
        $personal = DB::table('global.personal')
            ->select('global.personal.*', DB::raw("CONCAT(global.personal.nombres, ' ', global.personal.apellidos) as nombre_completo"))
            ->orderBy('nombres')->get();
        $tramos = DB::table('global.tramos')->orderBy('origen')->get();
        return view('facturacion.form', compact('ingreso', 'vehiculos', 'personal', 'tramos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_vehiculo' => 'required|integer',
            'concepto' => 'nullable|string|max:200',
            'monto' => 'required|numeric',
            'fecha_ingreso' => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'observaciones' => 'nullable|string',
            'cliente_nombre' => 'nullable|string|max:200',
            'cliente_telefono' => 'nullable|string|max:30',
            'origen' => 'nullable|string|max:200',
            'destino' => 'nullable|string|max:200',
            'toneladas' => 'nullable|numeric',
            'kilometraje_conducido' => 'nullable|numeric',
            'id_personal' => 'nullable|integer',
            'tipo_pago' => 'nullable|string|max:30',
            'nro_documento' => 'nullable|string|max:20',
        ]);

        $validated['estado_factura'] = 'PENDIENTE';

        $ultimo = DB::table('global.ingresos')
            ->where('nro_documento', 'like', 'I_%')
            ->orderBy('id_ingreso', 'desc')
            ->first();
        $contador = $ultimo ? intval(substr($ultimo->nro_documento, 2)) + 1 : 1;
        $validated['nro_documento'] = $validated['nro_documento'] ?? 'I_' . str_pad($contador, 5, '0', STR_PAD_LEFT);

        $id = DB::table('global.ingresos')->insertGetId($validated, 'id_ingreso');
        $ingreso = DB::table('global.ingresos')->where('id_ingreso', $id)->first();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Flete {$ingreso->nro_documento} registrado exitosamente",
                'nro_documento' => $ingreso->nro_documento,
                'id_ingreso' => $id,
            ]);
        }
        return redirect()->route('facturacion.index')->with('success', "Flete {$ingreso->nro_documento} registrado exitosamente");
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_vehiculo' => 'required|integer',
            'concepto' => 'required|string|max:200',
            'monto' => 'required|numeric',
            'fecha_ingreso' => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
            'observaciones' => 'nullable|string',
            'cliente_nombre' => 'nullable|string|max:200',
            'cliente_telefono' => 'nullable|string|max:30',
            'origen' => 'nullable|string|max:200',
            'destino' => 'nullable|string|max:200',
            'toneladas' => 'nullable|numeric',
            'kilometraje_conducido' => 'nullable|numeric',
            'id_personal' => 'nullable|integer',
            'tipo_pago' => 'nullable|string|max:30',
        ]);

        DB::table('global.ingresos')->where('id_ingreso', $id)->update($validated);
        return redirect()->route('facturacion.index')->with('success', 'Flete actualizado exitosamente');
    }

    public function destroy($id)
    {
        DB::table('global.ingresos')->where('id_ingreso', $id)->update(['estado_factura' => 'ANULADA']);
        return redirect()->route('facturacion.index')->with('success', 'Flete anulado');
    }

    public function apiList()
    {
        $data = DB::table('global.ingresos')
            ->select('numero_factura', 'fecha_factura', 'cliente_nombre',
                DB::raw('COUNT(*) as cantidad_fletes'),
                DB::raw('SUM(monto) as total_monto'),
                'estado_factura')
            ->whereNotNull('numero_factura')
            ->where('estado_factura', '!=', 'ANULADA')
            ->groupBy('numero_factura', 'fecha_factura', 'cliente_nombre', 'estado_factura')
            ->orderByDesc('fecha_factura')
            ->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiPendientes(Request $request)
    {
        $query = DB::table('global.ingresos')
            ->leftJoin('global.vehiculos', 'global.ingresos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->where('global.ingresos.estado_factura', 'PENDIENTE')
            ->select('global.ingresos.*', 'global.vehiculos.placa_vehiculo',
                DB::raw('COALESCE(global.ingresos.conductor_asignado, global.vehiculos.conductor_asignado) as chofer'));

        if ($request->fecha_inicio) $query->where('global.ingresos.fecha_ingreso', '>=', $request->fecha_inicio);
        if ($request->fecha_fin) $query->where('global.ingresos.fecha_ingreso', '<=', $request->fecha_fin);
        if ($request->cliente) $query->where('global.ingresos.cliente_nombre', 'ilike', "%{$request->cliente}%");

        $page = $request->page ?? 1;
        $limit = $request->limit ?? 50;
        $total = $query->count();
        $data = $query->orderByDesc('global.ingresos.fecha_ingreso')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => ['total' => $total, 'page' => (int)$page, 'limit' => (int)$limit]
        ]);
    }

    public function apiFletesByFactura($numeroFactura)
    {
        $data = DB::table('global.ingresos')
            ->leftJoin('global.vehiculos', 'global.ingresos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->where('global.ingresos.numero_factura', $numeroFactura)
            ->select('global.ingresos.*', 'global.vehiculos.placa_vehiculo')
            ->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiBatchFacturar(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'numero_factura' => 'required|string|max:50',
            'fecha_factura' => 'required|date',
            'cliente_nombre' => 'required|string|max:200',
        ]);

        DB::table('global.ingresos')
            ->whereIn('id_ingreso', $request->ids)
            ->update([
                'numero_factura' => $request->numero_factura,
                'fecha_factura' => $request->fecha_factura,
                'cliente_nombre' => $request->cliente_nombre,
                'estado_factura' => 'FACTURADA',
            ]);

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' flete(s) facturados exitosamente'
        ]);
    }

    public function apiToggleCobrado(Request $request)
    {
        $request->validate([
            'numero_factura' => 'required|string',
            'estado' => 'required|string|in:COBRADO,FACTURADA',
        ]);

        DB::table('global.ingresos')
            ->where('numero_factura', $request->numero_factura)
            ->update(['estado_factura' => $request->estado]);

        return response()->json(['success' => true, 'message' => 'Estado actualizado']);
    }

    public function apiAll(Request $request)
    {
        $query = DB::table('global.ingresos')
            ->leftJoin('global.vehiculos', 'global.ingresos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->leftJoin('global.personal', 'global.ingresos.id_personal', '=', 'global.personal.id_personal')
            ->select('global.ingresos.*', 'global.vehiculos.placa_vehiculo',
                DB::raw("CONCAT(global.personal.nombres, ' ', global.personal.apellidos) as conductor_nombre"));

        if ($request->estado) $query->where('global.ingresos.estado_factura', $request->estado);
        if ($request->fecha_inicio) $query->where('global.ingresos.fecha_ingreso', '>=', $request->fecha_inicio);
        if ($request->fecha_fin) $query->where('global.ingresos.fecha_ingreso', '<=', $request->fecha_fin);

        $data = $query->orderByDesc('global.ingresos.fecha_ingreso')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiShow($id)
    {
        $ingreso = DB::table('global.ingresos')
            ->leftJoin('global.vehiculos', 'global.ingresos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->leftJoin('global.personal', 'global.ingresos.id_personal', '=', 'global.personal.id_personal')
            ->select('global.ingresos.*', 'global.vehiculos.placa_vehiculo',
                DB::raw("CONCAT(global.personal.nombres, ' ', global.personal.apellidos) as conductor_nombre"))
            ->where('global.ingresos.id_ingreso', $id)
            ->first();
        return response()->json(['success' => true, 'data' => $ingreso]);
    }
}
