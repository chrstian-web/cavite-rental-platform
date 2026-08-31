<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // anyone may attempt to register
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'digits:11'],
            'password' => ['required', 'confirmed', Password::defaults()],
            // Only 'owner' or 'tenant' may self-register.
            // 'super_admin' and 'manager' accounts are created by an admin (Step 3+).
            'role' => ['required', 'in:owner,tenant'],
        ];
    }
}
