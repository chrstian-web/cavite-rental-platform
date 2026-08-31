<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'register' ? 'Create Account' : 'Sign In' }} · Cavite Rentals</title>
    <link rel="stylesheet" href="{{ asset('css/auth-card.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="auth-page">

    @php
        // Both forms live in the same DOM and can be visually toggled without
        // a page reload, so after a failed submission we trust which form was
        // ACTUALLY posted (_form) over which route rendered the page — otherwise
        // a registration error could redirect back showing the sign-in panel.
        $activePanel = match (old('_form')) {
            'signup' => 'register',
            'signin' => 'login',
            default => $mode,
        };
    @endphp


    <div class="auth-container {{ $activePanel === 'register' ? 'right-panel-active' : '' }}" id="authContainer">

        {{-- SIGN UP FORM --}}
        <div class="form-container sign-up-container">
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <input type="hidden" name="_form" value="signup">
                <h1>Create Account</h1>

                <div class="social-icons">
                    <a href="#" onclick="return false;" title="Social sign-up isn't available yet">G+</a>
                    <a href="#" onclick="return false;" title="Social sign-up isn't available yet">f</a>
                    <a href="#" onclick="return false;" title="Social sign-up isn't available yet">Gh</a>
                    <a href="#" onclick="return false;" title="Social sign-up isn't available yet">in</a>
                </div>
                <p>or use your email for registration</p>

                <div class="name-row">
                    <input type="text" name="first_name" placeholder="First name" value="{{ old('first_name') }}" required>
                    <input type="text" name="last_name" placeholder="Last name" value="{{ old('last_name') }}" required>
                </div>
                @error('first_name') <span class="field-error">{{ $message }}</span> @enderror
                @error('last_name') <span class="field-error">{{ $message }}</span> @enderror

                <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
                @error('email') <span class="field-error">{{ $message }}</span> @enderror

                <select name="role" required>
                    <option value="tenant" @selected(old('role', 'tenant') === 'tenant')>I'm a Tenant / Renter</option>
                    <option value="owner" @selected(old('role') === 'owner')>I'm a Property Owner</option>
                </select>

                <input type="password" name="password" placeholder="Password" required>
                @error('password') <span class="field-error">{{ $message }}</span> @enderror

                <input type="password" name="password_confirmation" placeholder="Confirm password" required>

                <button type="submit" class="submit-btn">Sign Up</button>
            </form>
        </div>

        {{-- SIGN IN FORM --}}
        <div class="form-container sign-in-container">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="_form" value="signin">
                <h1>Sign In</h1>

                <div class="social-icons">
                    <a href="#" onclick="return false;" title="Social sign-in isn't available yet">G+</a>
                    <a href="#" onclick="return false;" title="Social sign-in isn't available yet">f</a>
                    <a href="#" onclick="return false;" title="Social sign-in isn't available yet">Gh</a>
                    <a href="#" onclick="return false;" title="Social sign-in isn't available yet">in</a>
                </div>
                <p>or use your account</p>

                <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required autofocus>
                @error('email') <span class="field-error">{{ $message }}</span> @enderror

                <input type="password" name="password" placeholder="Password" required>
                @error('password') <span class="field-error">{{ $message }}</span> @enderror

                <a href="{{ route('password.request') }}" class="small-link">Forgot your password?</a>
                <button type="submit" class="submit-btn">Sign In</button>
            </form>
        </div>

        {{-- OVERLAY --}}
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all of site features</p>
                    <button type="button" class="ghost-btn" id="showSignIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Register with your personal details to start using Cavite Rentals</p>
                    <button type="button" class="ghost-btn" id="showSignUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Fallback toggle for narrow/mobile screens, where the overlay is hidden --}}
    <p class="mobile-toggle">
        <a href="{{ route($activePanel === 'register' ? 'login' : 'register') }}" class="small-link">
            {{ $activePanel === 'register' ? 'Already have an account? Sign in' : "Don't have an account? Sign up" }}
        </a>
    </p>

    <script>
        const container = document.getElementById('authContainer');
        document.getElementById('showSignUp').addEventListener('click', () => container.classList.add('right-panel-active'));
        document.getElementById('showSignIn').addEventListener('click', () => container.classList.remove('right-panel-active'));

        @if ($errors->any())
            Swal.fire({
                toast: true, position: 'top-end', icon: 'error',
                title: @json($errors->first()), showConfirmButton: false, timer: 4500, timerProgressBar: true,
            });
        @endif
        @if (session('status'))
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: @json(session('status')), showConfirmButton: false, timer: 3500, timerProgressBar: true,
            });
        @endif
    </script>
</body>
</html>
