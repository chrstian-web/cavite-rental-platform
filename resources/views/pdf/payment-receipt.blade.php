<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 6px 0; vertical-align: top; }
        td.label { width: 200px; color: #64748b; }
        .section { margin-top: 24px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #f0fdf4; color: #15803d; font-weight: bold; }
        .footer { margin-top: 48px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Payment Receipt</h1>
    <p class="muted">Receipt for Payment #{{ $payment->id }} &middot; Generated {{ now()->format('F j, Y') }}</p>

    <span class="badge">PAID</span>

    <div class="section">
        <table>
            <tr><td class="label">Property</td><td>{{ $payment->contract->property->name }}</td></tr>
            <tr><td class="label">Tenant</td><td>{{ $payment->contract->tenant->first_name }} {{ $payment->contract->tenant->last_name }} ({{ $payment->contract->tenant->email }})</td></tr>
            <tr><td class="label">Amount</td><td>₱{{ number_format($payment->amount, 2) }}</td></tr>
            <tr><td class="label">Due date</td><td>{{ $payment->due_date->format('F j, Y') }}</td></tr>
            <tr><td class="label">Payment date</td><td>{{ $payment->payment_date?->format('F j, Y') ?? '—' }}</td></tr>
            <tr><td class="label">Payment method</td><td>{{ $payment->payment_method ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : '—' }}</td></tr>
            <tr><td class="label">Reference number</td><td>{{ $payment->reference_number ?? '—' }}</td></tr>
            <tr><td class="label">Approved by</td><td>{{ $payment->reviewer ? $payment->reviewer->first_name.' '.$payment->reviewer->last_name : '—' }}</td></tr>
            <tr><td class="label">Approved on</td><td>{{ $payment->reviewed_at?->format('F j, Y g:i A') ?? '—' }}</td></tr>
        </table>
    </div>

    <p class="footer">This receipt was generated automatically by Cavite Rental Platform and reflects the payment record as of the generation date above.</p>
</body>
</html>
