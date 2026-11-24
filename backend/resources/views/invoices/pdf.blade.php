<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .details { margin: 24px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; }
        .cost { text-align: right; white-space: nowrap; }
        .total td { font-weight: bold; border-top: 2px solid #1f2937; border-bottom: none; }
    </style>
</head>
<body>
    <h1>Invoice #{{ $invoice->id }}</h1>
    <div class="muted">{{ $invoice->created_at->format('Y-m-d') }}</div>

    <div class="details">
        <div><strong>Client:</strong> {{ $invoice->project->client->name }}</div>
        <div><strong>Project:</strong> {{ $invoice->project->name }}</div>
        @if ($invoice->project->client->team)
            <div><strong>Team:</strong> {{ $invoice->project->client->team->name }}</div>
        @endif
        @if ($invoice->creator)
            <div><strong>Issued by:</strong> {{ $invoice->creator->name }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="cost">Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="cost">{{ number_format($item['cost'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>Total</td>
                <td class="cost">{{ number_format($invoice->total(), 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
