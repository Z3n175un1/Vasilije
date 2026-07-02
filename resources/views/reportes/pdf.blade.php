<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Gestión - VASILIJE</title>
    <style>
        @page {
            margin: 15mm 12mm 20mm 12mm;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 3px solid #1a1a1a;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }
        .logo-box {
            width: 70px;
            height: 70px;
            background: #1a1a1a;
            color: #ffc107;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28pt;
            font-weight: 900;
            flex-shrink: 0;
        }
        .company-info {
            flex: 1;
        }
        .company-name {
            font-size: 20pt;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #1a1a1a;
            margin: 0 0 2px 0;
            line-height: 1.1;
        }
        .company-details {
            font-size: 7.5pt;
            color: #555;
            line-height: 1.5;
        }
        .company-details strong {
            color: #1a1a1a;
        }
        .report-title-section {
            text-align: center;
            margin-bottom: 16px;
            padding: 10px 0;
            border-bottom: 2px solid #ddd;
        }
        .report-title {
            font-size: 13pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #1a1a1a;
            margin: 0 0 3px 0;
        }
        .report-period {
            font-size: 9pt;
            font-weight: 700;
            color: #666;
            margin: 0;
        }
        .summary-grid {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 16px;
        }
        .summary-box {
            flex: 1;
            padding: 10px 8px;
            text-align: center;
            border: 2.5px solid #1a1a1a;
        }
        .summary-box.ingresos { border-color: #007400; background: #f0fff0; }
        .summary-box.egresos { border-color: #cc0000; background: #fff0f0; }
        .summary-box.balance { border-color: #1a1a1a; background: #fffde7; }
        .summary-label {
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.5px;
        }
        .summary-value {
            font-size: 14pt;
            font-weight: 900;
            margin-top: 3px;
        }
        .summary-box.ingresos .summary-value { color: #007400; }
        .summary-box.egresos .summary-value { color: #cc0000; }
        .summary-box.balance .summary-value { color: #1a1a1a; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        thead th {
            background: #1a1a1a;
            color: #ffc107;
            font-weight: 800;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 8px 6px;
            border: 1px solid #1a1a1a;
            text-align: left;
        }
        thead th.right { text-align: right; }
        thead th.center { text-align: center; }
        tbody td {
            padding: 5px 6px;
            border: 1px solid #ddd;
            font-size: 7.5pt;
            vertical-align: middle;
        }
        tbody td.right { text-align: right; }
        tbody td.center { text-align: center; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody tr.row-ingreso { border-left: 3px solid #007400; }
        tbody tr.row-egreso { border-left: 3px solid #cc0000; }
        .badge-tipo {
            display: inline-block;
            padding: 1px 8px;
            font-weight: 800;
            font-size: 6pt;
            letter-spacing: 0.4px;
            border: 1.5px solid #1a1a1a;
        }
        .badge-ingreso { background: #e2ffd6; color: #007400; }
        .badge-egreso { background: #ffdcd6; color: #cc0000; }
        .footer-total {
            background: #1a1a1a;
            color: #ffc107;
            font-weight: 800;
            font-size: 8.5pt;
            padding: 8px 10px;
            text-align: right;
            letter-spacing: 0.4px;
        }
        .footer-note {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 2px solid #ddd;
            font-size: 6.5pt;
            color: #999;
            text-align: center;
        }
        .watermark {
            position: fixed;
            bottom: 8mm;
            right: 8mm;
            font-size: 42pt;
            color: rgba(0,0,0,0.03);
            font-weight: 900;
            transform: rotate(-15deg);
            z-index: -1;
        }
    </style>
</head>
<body>

    <div class="watermark">VASILIJE</div>

    <!-- TOP BAR: Logo + Company Info -->
    <div class="top-bar">
        <div class="logo-box">V</div>
        <div class="company-info">
            <div class="company-name">VASILIJE</div>
            <div class="company-details">
                <strong>NIT:</strong> 123456789012 &bull;
                <strong>Santa Cruz - Bolivia</strong><br>
                <strong>Dirección:</strong> Av. Principal N° 1234, Zona Central
            </div>
        </div>
    </div>

    <!-- REPORT TITLE -->
    <div class="report-title-section">
        <h2 class="report-title">Reporte de Fletes y Salidas</h2>
        <p class="report-period">{{ date('d/m/Y', strtotime($fechaInicio)) }} al {{ date('d/m/Y', strtotime($fechaFin)) }}</p>
    </div>

    <!-- SUMMARY -->
    @php
        $balance = $totalIngresos - $totalEgresos;
    @endphp
    <div class="summary-grid">
        <div class="summary-box ingresos">
            <div class="summary-label">Total Ingresos</div>
            <div class="summary-value">Bs. {{ number_format($totalIngresos, 2, ',', '.') }}</div>
        </div>
        <div class="summary-box egresos">
            <div class="summary-label">Total Egresos</div>
            <div class="summary-value">Bs. {{ number_format($totalEgresos, 2, ',', '.') }}</div>
        </div>
        <div class="summary-box balance">
            <div class="summary-label">Balance Neto</div>
            <div class="summary-value">Bs. {{ number_format($balance, 2, ',', '.') }}</div>
        </div>
    </div>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th style="width:8%;">N° Doc</th>
                <th style="width:10%;">Fecha</th>
                <th style="width:7%;">Tipo</th>
                <th style="width:25%;">Recorrido</th>
                <th style="width:9%;">Unidad</th>
                <th style="width:12%;">Gastos</th>
                <th class="right" style="width:12%;">Ingreso</th>
                <th class="right" style="width:12%;">Egreso</th>
                <th style="width:5%;">Detalle</th>
            </tr>
        </thead>
        <tbody>
            @forelse($todo as $item)
                @php
                    $recorrido = $item->tipo_registro === 'INGRESO'
                        ? ($item->origen ?? '') . ($item->destino ? ' → ' . $item->destino : '')
                        : ($item->concepto ?? '—');
                    $gastos = $item->tipo_registro === 'INGRESO' ? 'FLETE' : ($item->tipo_gasto ?? '—');
                    $detalle = $item->tipo_registro === 'INGRESO'
                        ? ($item->cliente_nombre ?? '—')
                        : ($item->proveedor ?? '—');
                @endphp
                <tr class="row-{{ strtolower($item->tipo_registro) }}">
                    <td class="center" style="font-weight:700;font-family:monospace;">{{ $item->nro_documento ?? '—' }}</td>
                    <td style="font-weight:600;">{{ $item->fecha ? date('d/m/Y', strtotime($item->fecha)) : '—' }}</td>
                    <td class="center">
                        <span class="badge-tipo {{ $item->tipo_registro === 'INGRESO' ? 'badge-ingreso' : 'badge-egreso' }}">
                            {{ $item->tipo_registro === 'INGRESO' ? '↑' : '↓' }}
                        </span>
                    </td>
                    <td style="font-weight:600;">{{ $recorrido ?: '—' }}</td>
                    <td class="center">{{ $item->placa_vehiculo ?? '—' }}</td>
                    <td style="font-weight:600;">{{ $gastos }}</td>
                    <td class="right" style="font-weight:700;color:#007400;">
                        {{ $item->tipo_registro === 'INGRESO' ? number_format($item->ingreso, 2, ',', '.') : '—' }}
                    </td>
                    <td class="right" style="font-weight:700;color:#cc0000;">
                        {{ $item->tipo_registro === 'GASTO' ? number_format($item->egreso, 2, ',', '.') : '—' }}
                    </td>
                    <td style="font-size:6.5pt;">{{ $detalle }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:25px;font-weight:700;color:#999;">
                        No se encontraron registros para el período seleccionado
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER TOTALS -->
    <div class="footer-total">
        <span style="margin-right:20px;">TOTAL INGRESOS: Bs. {{ number_format($totalIngresos, 2, ',', '.') }}</span>
        <span style="margin-right:20px;">|</span>
        <span style="margin-right:20px;">TOTAL EGRESOS: Bs. {{ number_format($totalEgresos, 2, ',', '.') }}</span>
        <span style="margin-right:20px;">|</span>
        <span>BALANCE: Bs. {{ number_format($balance, 2, ',', '.') }}</span>
    </div>

    <!-- FOOTER NOTE -->
    <div class="footer-note">
        VASILIJE - Sistema de Control de Gestión de Transporte &bull;
        Generado {{ date('d/m/Y \a \l\a\s H:i') }} &bull;
        {{ count($todo) }} registro(s) &bull;
        Documento oficial de gestión interna
    </div>

</body>
</html>