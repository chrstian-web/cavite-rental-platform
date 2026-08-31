@extends('layouts.guest')

@section('title', 'Create an account')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Create your account</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">First name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required autofocus
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                @error('first_name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Last name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" required
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                @error('last_name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Phone (optional)</label>
            <input type="text" name="phone" value="{{ old('phone') }}" inputmode="numeric" maxlength="11" pattern="[0-9]{11}"
                placeholder="09171234567"
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-slate-400 mt-1">11 digits, numbers only (e.g. 09171234567)</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">I am registering as a...</label>
            <select name="role" required class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                <option value="tenant" @selected(old('role') === 'tenant')>Tenant / Renter</option>
                <option value="owner" @selected(old('role') === 'owner')>Property Owner / Landlord</option>
            </select>
            @error('role') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input type="password" name="password" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg py-2.5">
            Create account
        </button>

        <p class="text-center text-sm text-slate-500">
            Already have an account? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Log in</a>
        </p>
    </form>
@endsection
