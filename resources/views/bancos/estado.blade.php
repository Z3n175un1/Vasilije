@extends('layouts.master')

@section('title', 'Estado de Cuenta')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">ESTADO DE CUENTA</h1>
            <p class="font-bold small text-black uppercase">Detalle de movimientos del banco</p>
        </div>
        <a href="{{ route('bancos.index') }}" class="btn-bento btn-bento-outline py-1 px-2 fs-mid font-bold rounded-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> VOLVER
        </a>
    </header>

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="bento-card p-4" style="border:4px solid #000;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:56px;height:56px;background:#2f2c79;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;border:3px solid #000;flex-shrink:0;"><i class="fas fa-university"></i></div>
                    <div>
                        <div class="fw-black fs-mid">{{ $banco->nombre_banco }}</div>
                        <div class="small fw-bold text-muted">CUENTA N° {{ $banco->numero_cuenta }} • {{ $banco->tipo_cuenta }} • {{ $banco->moneda }}</div>
                        <div class="small fw-bold">TITULAR: {{ $banco->titular }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bento-card p-4 h-100" style="border:4px solid #000;text-align:center;">
                <div class="small fw-bold text-uppercase opacity-50">Saldo Actual</div>
                <div class="fw-black" style="font-size:1.9rem;color:#007400;">Bs. {{ number_format($saldoActual, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="summary-card p-3" style="border:4px solid #000;text-align:center;">
                <div class="label small fw-bold text-uppercase opacity-50">Saldo Inicial</div>
                <div class="fw-bold fs-5">Bs. {{ number_format($banco->saldo_inicial, 2, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card p-3" style="border:4px solid #000;text-align:center;">
                <div class="label small fw-bold text-uppercase opacity-50">Movimientos</div>
                <div class="fw-bold fs-5">{{ $movimientos->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card p-3" style="border:4px solid #000;text-align:center;">
                <div class="label small fw-bold text-uppercase opacity-50">Total Débitos</div>
                <div class="fw-bold fs-5" style="color:#cc0000;">Bs. {{ number_format($totalDebitos, 2, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card p-3" style="border:4px solid #000;text-align:center;">
                <div class="label small fw-bold text-uppercase opacity-50">Saldo Final</div>
                <div class="fw-bold fs-5" style="color:#007400;">Bs. {{ number_format($saldoActual, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="bento-card p-0" style="border:4px solid #000;overflow:hidden;">
        <div class="bg-white text-black font-bold p-3 border-bottom border-black d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i> Movimientos del Banco</span>
        </div>
        <div class="table-responsive-brutalist">
            <table class="table-excel mb-0" style="font-size:.9rem;">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>N° Doc</th>
                        <th>Concepto</th>
                        <th>Proveedor</th>
                        <th>Condición</th>
                        <th class="text-end">Débito</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $m)
                        <tr>
                            <td class="fw-bold">{{ $m['fecha'] }}</td>
                            <td>
                                <span class="badge fw-bold px-2 py-1" style="background:{{ $m['tipo'] === 'GASTO' ? '#ffdcd6' : '#d4edda' }};color:#000;border:2px solid #000;">{{ $m['tipo'] }}</span>
                            </td>
                            <td class="fw-bold font-monospace">{{ $m['nro_documento'] ?? '—' }}</td>
                            <td class="fw-bold">{{ $m['concepto'] ?? '—' }}</td>
                            <td>{{ $m['proveedor'] ?? '—' }}</td>
                            <td>{{ $m['condicion_pago'] ?? 'CONTADO' }}</td>
                            <td class="text-end fw-bold" style="color:#cc0000;">Bs. {{ number_format((float)$m['monto'], 2, ',', '.') }}</td>
                            <td class="text-end fw-bold">Bs. {{ number_format((float)$m['saldo'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5 opacity-50">SIN MOVIMIENTOS PARA ESTE BANCO</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
