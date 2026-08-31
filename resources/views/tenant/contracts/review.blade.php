@extends('layouts.app')

@section('title', 'Leave a Review')

@section('content')
    <a href="{{ route('tenant.contracts.show', $contract) }}" class="text-sm text-blue-600 hover:underline">&larr; Contract</a>

    <h1 class="text-xl font-semibold text-slate-900 mt-4 mb-6">Review {{ $contract->property->name }}</h1>

    <form method="POST" action="{{ route('tenant.contracts.review.store', $contract) }}" class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 max-w-lg">
        @csrf

        @foreach ([
            'rating' => 'Overall rating',
            'cleanliness_rating' => 'Cleanliness',
            'location_rating' => 'Location',
            'amenities_rating' => 'Amenities',
            'value_rating' => 'Value for money',
            'management_rating' => 'Management responsiveness',
        ] as $field => $label)
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}</label>
                <select name="{{ $field }}" {{ $field === 'rating' ? 'required' : '' }} class="w-full md:w-40 rounded-lg border-slate-300">
                    @if ($field !== 'rating')<option value="">—</option>@endif
                    @for ($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
            </div>
        @endforeach

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Comment (optional)</label>
            <textarea name="comment" rows="4" class="w-full rounded-lg border-slate-300"></textarea>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
            Submit review
        </button>
    </form>
@endsection
