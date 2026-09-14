@extends('layouts.app')

@section('title', 'Login — Daksa ERP')

@section('content')
<div class="grid min-h-screen lg:grid-cols-2">
    <section class="relative hidden overflow-hidden bg-ink-950 text-white lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(86,142,144,0.35),_transparent_55%)]"></div>
        <div class="relative">
            <div class="text-3xl font-semibold tracking-tight">Daksa</div>
            <p class="mt-2 max-w-sm text-sm text-ink-200">Multi-tenant ERP foundation — tenants, plans, and custom roles in one workspace.</p>
        </div>
        <div class="relative space-y-3 text-sm text-ink-200">
            <p>Platform admins manage companies.</p>
            <p>Tenant admins control who can act inside enabled modules.</p>
        </div>
    </section>

    <section class="flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 lg:hidden">
                <div class="text-2xl font-semibold tracking-tight text-ink-950">Daksa</div>
                <p class="text-sm text-ink-500">Sign in to continue</p>
            </div>

            <h1 class="text-2xl font-semibold text-ink-950">Sign in</h1>
            <p class="mt-1 text-sm text-ink-500">Use your company account or platform credentials.</p>

            <x-card class="mt-6 space-y-4">
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <x-input label="Email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
                    <x-input label="Password" name="password" type="password" required autocomplete="current-password" />
                    <label class="flex items-center gap-2 text-sm text-ink-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-line">
                        Remember me
                    </label>
                    <x-button class="w-full">Login</x-button>
                </form>
            </x-card>
        </div>
    </section>
</div>
@endsection
