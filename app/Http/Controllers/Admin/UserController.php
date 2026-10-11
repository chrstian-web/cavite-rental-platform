<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('role')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function editPassword(User $user): View
    {
        return view('admin.users.edit-password', compact('user'));
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $displayName = trim("{$user->first_name} {$user->last_name}");

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Password updated for {$displayName}.");
    }

    /**
     * Soft-delete a user (the row is kept, so contracts, payments and history
     * stay intact; the person simply can no longer log in).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $displayName = trim("{$user->first_name} {$user->last_name}");

        if ($user->is($request->user())) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }

        if ($user->isSuperAdmin()) {
            return back()->withErrors(['delete' => 'Administrator accounts cannot be deleted here.']);
        }

        $hasActiveContract = RentalContract::query()
            ->where('status', 'active')
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('owner_id', $user->id))
            ->exists();

        if ($hasActiveContract) {
            return back()->withErrors(['delete' => "{$displayName} still has an active rental contract. End the contract first."]);
        }

        $user->tokens()->delete();
        $user->delete();

        return redirect()
            ->route('admin.users.index', $request->only('search'))
            ->with('status', "{$displayName} was deleted.");
    }

    /** Deleted users, so an accidental delete can be undone. */
    public function deleted(Request $request): View
    {
        $users = User::onlyTrashed()
            ->with('role')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.deleted', compact('users'));
    }

    public function restore(User $user): RedirectResponse
    {
        $user->restore();

        $displayName = trim("{$user->first_name} {$user->last_name}");

        return redirect()
            ->route('admin.users.deleted')
            ->with('status', "{$displayName} was restored and can log in again.");
    }
}
