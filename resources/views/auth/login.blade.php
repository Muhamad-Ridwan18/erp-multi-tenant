@extends('layouts.app')

@section('title', 'Login — Daksa ERP')

@section('content')
    <div class="text-center mb-4">
        <h1 class="h2 mb-1">Daksa</h1>
        <p class="text-secondary">
            @if (! empty($isTenantHost) && $tenant)
                {{ $tenant->name }} — tenant workspace
            @else
                Platform console — manage tenants and plans
            @endif
        </p>
    </div>

    <div class="card card-md">
        <div class="card-body">
            <h2 class="h3 text-center mb-3">Sign in</h2>
            <form method="POST" action="{{ route('login') }}" autocomplete="off">
                @csrf
                <x-input label="Email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
                <x-input label="Password" name="password" type="password" required autocomplete="current-password" />
                <div class="mb-3">
                    <label class="form-check">
                        <input type="checkbox" name="remember" value="1" class="form-check-input">
                        <span class="form-check-label">Remember me</span>
                    </label>
                </div>
                <div class="form-footer">
                    <x-button class="w-100">Login</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
