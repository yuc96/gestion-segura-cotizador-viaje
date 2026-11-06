<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $quote->number() }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1c1915; font-size: 12px; line-height: 1.45; }
        h1 { font-size: 22px; margin: 0; color: #14304f; }
        h2 { font-size: 13px; margin: 0 0 8px; color: #14304f; letter-spacing: 0.04em; text-transform: uppercase; }
        .brand { font-size: 11px; letter-spacing: 0.16em; color: #8a6a12; font-weight: bold; }
        .muted { color: #5e584e; }
        .rule { height: 3px; background: #d89b12; margin: 14px 0 18px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { vertical-align: top; padding: 0; }
        .box { border: 1px solid #e3d9c6; padding: 12px 14px; }
        .box + .box { margin-top: 12px; }
        .lines td { padding: 6px 0; border-bottom: 1px solid #eee6d8; }
        .lines td:last-child { text-align: right; }
        .total td { padding-top: 10px; font-size: 16px; font-weight: bold; color: #14304f; border-bottom: none; }
        .status { display: inline-block; padding: 3px 8px; border: 1px solid #d89b12; color: #8a6a12; font-size: 11px; font-weight: bold; }
        .foot { margin-top: 22px; font-size: 10px; color: #5e584e; }
    </style>
</head>
<body>
    <table class="meta">
        <tr>
            <td>
                <div class="brand">GESTIÓN SEGURA</div>
                <h1>Seguro de viaje</h1>
                <div class="muted">Cotización {{ $quote->number() }}</div>
            </td>
            <td style="text-align: right;">
                <div class="status">{{ $quote->status->value }}</div>
                <div class="muted" style="margin-top: 8px;">Emitida {{ $quote->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                @if ($quote->contracted_at)
                    <div class="muted">Contratada {{ $quote->contracted_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                @endif
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <div class="box">
        <h2>Asegurado</h2>
        <div><strong>{{ $quote->insured->fullName() }}</strong></div>
        <div>Identificación: {{ $quote->insured->identification }}</div>
        <div>Correo: {{ $quote->insured->email }}</div>
        <div>Nacimiento: {{ $quote->insured->birth_date?->format('d/m/Y') }}</div>
    </div>

    <div class="box">
        <h2>Viaje</h2>
        <div><strong>{{ $quote->destination_name }}</strong> ({{ $quote->destination_code }})</div>
        <div>Región tarifaria: {{ $quote->pricing_region->label() }}</div>
        <div>Salida: {{ $quote->departure_date?->format('d/m/Y') }}</div>
        <div>Regreso: {{ $quote->return_date?->format('d/m/Y') }}</div>
        <div>Días cubiertos: {{ $quote->trip_days }}</div>
    </div>

    <div class="box">
        <h2>Prima</h2>
        <table class="lines">
            <tr>
                <td>Tarifa base ({{ $quote->trip_days }} días × USD {{ number_format($quote->daily_rate_cents / 100, 2) }})</td>
                <td>USD {{ number_format($quote->base_amount_cents / 100, 2) }}</td>
            </tr>
            <tr>
                <td>Recargo {{ $quote->pricing_region->label() }} ({{ $quote->surcharge_percent }}%)</td>
                <td>USD {{ number_format($quote->surcharge_amount_cents / 100, 2) }}</td>
            </tr>
            <tr class="total">
                <td>Total</td>
                <td>USD {{ number_format($quote->total_amount_cents / 100, 2) }}</td>
            </tr>
        </table>
    </div>

    <p class="foot">
        Este documento resume la cotización calculada por Gestión Segura. No procesa un pago ni reemplaza la póliza.
        Los montos quedan guardados con la cotización y no cambian si la tarifa se actualiza después.
        @if ($quote->status->value === 'Cotizado')
            El estado actual es Cotizado: la cobertura se registra cuando el cliente confirma la contratación.
        @else
            El seguro figura como Contratado desde la fecha indicada arriba.
        @endif
    </p>
</body>
</html>
