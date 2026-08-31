<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.card', ['mode' => 'register']);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::where('slug', $validated['role'])->firstOrFail();

        $user = User::create([
            'role_id' => $role->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            // Only owners go through the verification pipeline — every other
            // role stays null, meaning "not applicable."
            'owner_verification_status' => $validated['role'] === 'owner' ? 'not_submitted' : null,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Per the required flow: Register → Owner Verification, before ever
        // reaching the normal dashboard.
        if ($validated['role'] === 'owner') {
            return redirect()->route('owner.verification.show');
        }

        return redirect()->route('dashboard');
    }
}
