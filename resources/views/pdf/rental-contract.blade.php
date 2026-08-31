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
        .terms { white-space: pre-line; line-height: 1.5; }
        .signatures { margin-top: 48px; display: flex; }
        .sig-box { width: 45%; display: inline-block; margin-right: 5%; }
        .sig-line { border-top: 1px solid #1e293b; margin-top: 40px; padding-top: 4px; }
    </style>
</head>
<body>
    <h1>Rental Agreement</h1>
    <p class="muted">Contract #{{ $contract->id }} &middot; Generated {{ now()->format('F j, Y') }}</p>

    <table>
        <tr><td class="label">Property</td><td>{{ $contract->property->name }}</td></tr>
        <tr><td class="label">Address</td><td>{{ $contract->property->address_line }}</td></tr>
        <tr><td class="label">Unit / Room</td><td>{{ $contract->rentalSpace->space_number }}</td></tr>
        <tr><td class="label">Tenant</td><td>{{ $contract->tenant->first_name }} {{ $contract->tenant->last_name }} ({{ $contract->tenant->email }})</td></tr>
        <tr><td class="label">Owner / Landlord</td><td>{{ $contract->owner->first_name }} {{ $contract->owner->last_name }} ({{ $contract->owner->email }})</td></tr>
    </table>

    <div class="section">
        <table>
            <tr><td class="label">Monthly Rent</td><td>₱{{ number_format($contract->monthly_rent, 2) }}</td></tr>
            <tr><td class="label">Security Deposit</td><td>₱{{ number_format($contract->security_deposit, 2) }}</td></tr>
            <tr><td class="label">Advance Payment</td><td>₱{{ number_format($contract->advance_payment, 2) }}</td></tr>
            <tr><td class="label">Lease Start</td><td>{{ $contract->start_date->format('F j, Y') }}</td></tr>
            <tr><td class="label">Lease End</td><td>{{ $contract->end_date->format('F j, Y') }}</td></tr>
            <tr><td class="label">Status</td><td>{{ ucfirst($contract->status) }}</td></tr>
        </table>
    </div>

    @if ($contract->terms_and_conditions)
        <div class="section">
            <strong>Terms &amp; Conditions</strong>
            <p class="terms">{{ $contract->terms_and_conditions }}</p>
        </div>
    @endif

    <div class="signatures">
        <div class="sig-box">
            <div class="sig-line">Tenant Signature &middot; {{ $contract->tenant->first_name }} {{ $contract->tenant->last_name }}</div>
        </div>
        <div class="sig-box">
            <div class="sig-line">Owner Signature &middot; {{ $contract->owner->first_name }} {{ $contract->owner->last_name }}</div>
        </div>
    </div>
</body>
</html>
