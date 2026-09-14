@extends('layouts.app')

@section('title', 'Login — Daksa ERP')

@section('content')
    <div class="text-center mb-4">
        <a href="{{ url('/') }}" class="navbar-brand navbar-brand-autodark d-inline-flex align-items-center gap-2">
            <span class="avatar bg-primary text-white fw-bold">D</span>
            <span class="navbar-brand-text">Daksa</span>
        </a>
        <div class="text-secondary mt-2">
            @if (! empty($isTenantHost) && $tenant)
                {{ $tenant->name }} — tenant workspace
            @else
                Platform console
            @endif
        </div>
    </div>

    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Login to your account</h2>
            <form method="POST" action="{{ route('login') }}" autocomplete="off" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="your@email.com"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-2">
                    <label class="form-label" for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="Your password"
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-2">
                    <label class="form-check">
                        <input type="checkbox" name="remember" value="1" class="form-check-input">
                        <span class="form-check-label">Remember me on this device</span>
                    </label>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Sign in</button>
                </div>
            </form>
        </div>
    </div>
@endsection
