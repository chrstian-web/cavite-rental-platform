@extends('layouts.app')

@section('title', 'Submit Maintenance Request')

@section('content')
    <a href="{{ route('tenant.contracts.show', $contract) }}" class="text-sm text-blue-600 hover:underline">&larr; Contract</a>

    <h1 class="text-xl font-semibold text-slate-900 mt-4 mb-6">Submit maintenance request — {{ $contract->property->name }}</h1>

    <form method="POST" action="{{ route('tenant.maintenance.store', $contract) }}" enctype="multipart/form-data"
          class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 max-w-lg">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
            <select name="category" required class="w-full rounded-lg border-slate-300">
                @foreach (['plumbing', 'electrical', 'internet', 'furniture', 'air_conditioning', 'cleaning', 'security', 'other'] as $cat)
                    <option value="{{ $cat }}">{{ str($cat)->replace('_',' ')->title() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
            <select name="priority" required class="w-full rounded-lg border-slate-300">
                <option value="low">Low</option>
                <option value="normal" selected>Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
            <textarea name="description" rows="4" required class="w-full rounded-lg border-slate-300"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Photos (optional)</label>
            <input type="file" name="images[]" multiple accept="image/*" class="w-full text-sm">
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
            Submit request
        </button>
    </form>
@endsection
