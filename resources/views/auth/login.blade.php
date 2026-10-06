@extends('layouts.app') @section('title','Sign in') @section('content')
<div class="row justify-content-center align-items-center" style="min-height:65vh"><div class="col-12 col-md-8 col-lg-5 col-xl-4"><div class="card p-4 p-md-5"><div class="text-center mb-4"><img src="{{ asset('images/tooth-lounge-logo.png') }}" style="width:200px;max-height:120px;object-fit:contain" alt="The Tooth Lounge"><h1 class="h3 text-brand mt-3">Welcome back</h1><p class="text-secondary">Sign in to your clinic account.</p></div><form method="POST" action="{{ route('login.submit') }}">@csrf<div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" maxlength="254" value="{{ old('email') }}" autocomplete="username" required autofocus></div><div class="mb-4"><label class="form-label" for="password">Password</label><div class="position-relative"><input class="form-control pe-5" id="password" name="password" type="password" maxlength="1024" autocomplete="current-password" required><button class="password-visibility" type="button" aria-label="Hover to show password" aria-controls="password" aria-pressed="false" data-password-hover title="Hover to show password"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div><button class="btn btn-primary w-100">Sign in</button></form><p class="text-center mt-4 mb-0">New patient? <a href="{{ route('register') }}">Create an account</a></p></div></div></div>
<style>
    .password-visibility { position:absolute; top:50%; right:.65rem; display:grid; width:2rem; height:2rem; place-items:center; padding:0; transform:translateY(-50%); border:0; border-radius:.4rem; color:#60777d; background:transparent; }
    .password-visibility:hover, .password-visibility:focus-visible { color:var(--teal-deep); background:#e8f6f8; outline:2px solid transparent; }
</style>
@push('scripts')
<script>
    document.querySelectorAll('[data-password-hover]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        const icon = button.querySelector('i');
        let hovered = false;
        let keyboardFocused = false;
        let touchToggled = false;

        const updateVisibility = () => {
            const visible = hovered || keyboardFocused || touchToggled;
            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute('aria-label', visible ? 'Password visible' : 'Hover to show password');
            button.title = visible ? 'Password visible' : 'Hover to show password';
            icon.classList.toggle('bi-eye', !visible);
            icon.classList.toggle('bi-eye-slash', visible);
        };

        button.addEventListener('pointerenter', (event) => {
            if (event.pointerType === 'mouse') {
                hovered = true;
                touchToggled = false;
                updateVisibility();
            }
        });
        button.addEventListener('pointerleave', (event) => {
            if (event.pointerType === 'mouse') {
                hovered = false;
                updateVisibility();
            }
        });
        button.addEventListener('focus', () => {
            keyboardFocused = button.matches(':focus-visible');
            updateVisibility();
        });
        button.addEventListener('blur', () => {
            keyboardFocused = false;
            touchToggled = false;
            updateVisibility();
        });
        button.addEventListener('click', () => {
            if (window.matchMedia('(hover: none)').matches) {
                touchToggled = !touchToggled;
                updateVisibility();
            }
        });
    });
</script>
@endpush
@endsection
