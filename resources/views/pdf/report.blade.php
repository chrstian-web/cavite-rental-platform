<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .muted { color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 10px; border-bottom: 2px solid #cbd5e1; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        .summary { margin-top: 12px; padding: 8px; background: #f8fafc; border-radius: 4px; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ $report['title'] }}</h1>
    <p class="muted">
        Cavite Rental Platform &middot; Generated {{ now()->format('F j, Y g:i A') }}
        @if (!empty($filters))
            &middot; Filters:
            @foreach ($filters as $key => $value)
                {{ str($key)->replace('_', ' ')->title() }}: {{ $value }}@if(!$loop->last), @endif
            @endforeach
        @endif
    </p>

    @if (isset($report['summary']))
        <div class="summary">{{ $report['summary'] }}</div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($report['headers'] as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($report['headers']) }}">No data for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
