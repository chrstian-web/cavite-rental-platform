@if ($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
        <p class="text-sm font-medium text-red-800 mb-2">
            {{ $errors->count() === 1 ? 'There is 1 problem with your submission:' : 'There are '.$errors->count().' problems with your submission:' }}
        </p>
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
