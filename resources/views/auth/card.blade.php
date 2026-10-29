<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'register' ? 'Create Account' : 'Sign In' }} · Cavite Rentals</title>
    <link rel="stylesheet" href="{{ asset('css/auth-card.css') }}?v={{ @filemtime(public_path('css/auth-card.css')) }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('partials.button-effects')
    @include('partials.pwa')
</head>
<body class="auth-page">

    <div class="auth-orbs" aria-hidden="true"><span></span><span></span><span></span></div>

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


    <a href="{{ route('home') }}" class="auth-back" aria-label="Back to home" title="Back to home"></a>

    <a href="{{ route('home') }}" class="auth-brand">Cavite<span>Rentals</span></a>

    <div class="auth-container {{ $activePanel === 'register' ? 'right-panel-active' : '' }}" id="authContainer">

        {{-- SIGN UP FORM --}}
        <div class="form-container sign-up-container">
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <input type="hidden" name="_form" value="signup">
                <h1>Create Account</h1>

                <div class="name-row">
                    <div class="field field--plain">
                        <input type="text" id="signup-first" name="first_name" placeholder=" " value="{{ old('first_name') }}" required @if ($activePanel === 'register') autofocus @endif>
                        <label for="signup-first">First name</label>
                    </div>
                    <div class="field field--plain">
                        <input type="text" id="signup-last" name="last_name" placeholder=" " value="{{ old('last_name') }}" required>
                        <label for="signup-last">Last name</label>
                    </div>
                </div>
                @error('first_name') <span class="field-error">{{ $message }}</span> @enderror
                @error('last_name') <span class="field-error">{{ $message }}</span> @enderror

                <div class="field">
                    <input type="email" id="signup-email" name="email" placeholder=" " value="{{ old('email') }}" required>
                    <label for="signup-email">Email</label>
                </div>
                @error('email') <span class="field-error">{{ $message }}</span> @enderror

                <div class="field field--plain field--select">
                    <select id="signup-role" name="role" required>
                        <option value="tenant" @selected(old('role', 'tenant') === 'tenant')>I'm a Tenant / Renter</option>
                        <option value="owner" @selected(old('role') === 'owner')>I'm a Property Owner</option>
                    </select>
                    <label for="signup-role">I am a</label>
                </div>
                @error('role') <span class="field-error">{{ $message }}</span> @enderror

                <div class="field">
                    <input type="password" id="signup-password" name="password" placeholder=" " required>
                    <label for="signup-password">Password</label>
                </div>
                @error('password') <span class="field-error">{{ $message }}</span> @enderror

                <div class="field">
                    <input type="password" id="signup-password2" name="password_confirmation" placeholder=" " required>
                    <label for="signup-password2">Confirm password</label>
                </div>

                <button type="submit" class="submit-btn">Sign Up</button>
            </form>
        </div>

        {{-- SIGN IN FORM --}}
        <div class="form-container sign-in-container">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="_form" value="signin">
                <h1>Sign In</h1>

                <div class="field">
                    <input type="email" id="signin-email" name="email" placeholder=" " value="{{ old('email') }}" required @if ($activePanel !== 'register') autofocus @endif>
                    <label for="signin-email">Email</label>
                </div>
                @error('email') <span class="field-error">{{ $message }}</span> @enderror

                <div class="field">
                    <input type="password" id="signin-password" name="password" placeholder=" " required>
                    <label for="signin-password">Password</label>
                </div>
                @error('password') <span class="field-error">{{ $message }}</span> @enderror

                <a href="{{ route('password.request') }}" class="small-link">Forgot your password?</a>
                <button type="submit" class="submit-btn">Sign In</button>
            </form>
        </div>

        {{-- OVERLAY --}}
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    @include('partials.landing-mascots')
                    <h1>Welcome back.</h1>
                    <p>Sign in to pick up your searches, applications, and viewings.</p>
                    <button type="button" class="ghost-btn" id="showSignIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    @include('partials.landing-mascots')
                    <h1>New to Cavite Rentals?</h1>
                    <p>Create an account to save favorites, compare listings, and request viewings.</p>
                    <button type="button" class="ghost-btn" id="showSignUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Fallback toggle for narrow/mobile screens, where the overlay is hidden --}}
    <p class="mobile-toggle">
        <a href="{{ route($activePanel === 'register' ? 'login' : 'register') }}" class="mobile-toggle__link">
            <span class="mobile-toggle__text">{{ $activePanel === 'register' ? 'Already have an account?' : "Don't have an account?" }}</span>
            <span class="mobile-toggle__action">
                {{ $activePanel === 'register' ? 'Sign in' : 'Sign up' }}
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </span>
        </a>
    </p>

    <script>
        const container = document.getElementById('authContainer');
        document.getElementById('showSignUp').addEventListener('click', () => container.classList.add('right-panel-active'));
        document.getElementById('showSignIn').addEventListener('click', () => container.classList.remove('right-panel-active'));

        const EYE = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
        const EYE_OFF = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A10.7 10.7 0 0112 5c6.5 0 10 7 10 7a17 17 0 01-3.2 4.2M6.6 6.6A17 17 0 002 12s3.5 7 10 7c1.9 0 3.6-.5 5-1.3M9.9 9.9a3 3 0 004.2 4.2"/></svg>';
        document.querySelectorAll('.auth-container input[type="password"]').forEach((input) => {
            input.classList.add('is-password');
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'pw-toggle';
            toggle.setAttribute('aria-label', 'Show password');
            toggle.setAttribute('aria-pressed', 'false');
            toggle.innerHTML = EYE;
            toggle.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.innerHTML = show ? EYE_OFF : EYE;
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
            });
            input.closest('.field').appendChild(toggle);
        });

        @if ($errors->any())
            window.Swal && Swal.fire({
                toast: true, position: 'top-end', icon: 'error',
                title: @json($errors->first()), showConfirmButton: false, timer: 4500, timerProgressBar: true,
            });
        @endif
        @if (session('session_expired'))
            window.Swal && Swal.fire({
                toast: true, position: 'top-end', icon: 'warning',
                title: @json(session('session_expired')), showConfirmButton: false, timer: 5000, timerProgressBar: true,
            });
        @endif
        @if (session('status'))
            window.Swal && Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: @json(session('status')), showConfirmButton: false, timer: 3500, timerProgressBar: true,
            });
        @endif
    </script>
</body>
</html>
