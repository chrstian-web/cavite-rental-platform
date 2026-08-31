@extends('layouts.app')

@section('title', 'Owner Verifications')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Owner Verifications</h1>

    <form method="GET" class="mb-4">
        <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach (['needs_review', 'needs_additional_documents', 'approved', 'rejected'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ str($s)->replace('_',' ')->title() }}</option>
            @endforeach
        </select>
    </form>

    @if ($verifications->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">No submissions yet.</div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Owner</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($verifications as $verification)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $verification->user->first_name }} {{ $verification->user->last_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $verification->submitted_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full
                                    {{ match($verification->status) {
                                        'approved' => 'bg-green-50 text-green-700',
                                        'rejected' => 'bg-red-50 text-red-700',
                                        'needs_additional_documents' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-blue-50 text-blue-700',
                                    } }}">
                                    {{ str($verification->status)->replace('_',' ')->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.owner-verifications.show', $verification) }}" class="text-blue-600 hover:underline">Review</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $verifications->links() }}</div>
    @endif
@endsection
