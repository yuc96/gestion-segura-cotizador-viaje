<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $quote->number() }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1c1915; font-size: 12px; line-height: 1.45; }
        h1 { font-size: 22px; margin: 0; color: #233266; }
        h2 { font-size: 13px; margin: 0 0 8px; color: #233266; letter-spacing: 0.04em; text-transform: uppercase; }
        .brand { font-size: 11px; letter-spacing: 0.16em; color: #8a5a10; font-weight: bold; }
        .muted { color: #5e584e; }
        .rule { height: 3px; background: #fcb333; margin: 14px 0 18px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { vertical-align: top; padding: 0; }
        .box { border: 1px solid #e3d9c6; padding: 12px 14px; }
        .box + .box { margin-top: 12px; }
        .lines td { padding: 6px 0; border-bottom: 1px solid #eee6d8; }
        .lines td:last-child { text-align: right; }
        .total td { padding-top: 10px; font-size: 16px; font-weight: bold; color: #233266; border-bottom: none; }
        .status { display: inline-block; padding: 3px 8px; border: 1px solid #fcb333; color: #8a5a10; font-size: 11px; font-weight: bold; }
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
                <div class="muted" style="margin-top: 8px;">Emitida {{ $quote->createdAt?->format('d/m/Y H:i') }}</div>
                @if ($quote->contractedAt)
                    <div class="muted">Contratada {{ $quote->contractedAt->format('d/m/Y H:i') }}</div>
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
        <div>Nacimiento: {{ \DateTimeImmutable::createFromFormat('Y-m-d', $quote->insured->birthDate)->format('d/m/Y') }}</div>
    </div>

    <div class="box">
        <h2>Viaje</h2>
        <div><strong>{{ $quote->destination->name }}</strong> ({{ $quote->destination->code }})</div>
        <div>Región tarifaria: {{ $quote->destination->pricingRegion->label() }}</div>
        <div>Salida: {{ $quote->trip->departure->format('d/m/Y') }}</div>
        <div>Regreso: {{ $quote->trip->return->format('d/m/Y') }}</div>
        <div>Días cubiertos: {{ $quote->trip->days }}</div>
    </div>

    <div class="box">
        <h2>Prima</h2>
        <table class="lines">
            <tr>
                <td>Tarifa base ({{ $quote->trip->days }} días × USD {{ $quote->premium->dailyRate->toDecimal() }})</td>
                <td>USD {{ $quote->premium->base->toDecimal() }}</td>
            </tr>
            <tr>
                <td>Recargo {{ $quote->premium->region->label() }} ({{ $quote->premium->surchargePercent }}%)</td>
                <td>USD {{ $quote->premium->surcharge->toDecimal() }}</td>
            </tr>
            <tr class="total">
                <td>Total</td>
                <td>USD {{ $quote->premium->total->toDecimal() }}</td>
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
