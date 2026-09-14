@extends('layouts.app')

@section('title', 'Login — Daksa ERP')

@section('content')
<div class="max-w-md mx-auto mt-16">
    <h1 class="text-2xl font-semibold mb-2">Sign in</h1>
    <p class="text-slate-500 text-sm mb-6">Multi-tenant SaaS foundation — demo accounts in README.</p>

    <form method="POST" action="{{ route('login') }}" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>
        <button class="w-full rounded-lg bg-slate-900 text-white py-2.5 text-sm font-medium">Login</button>
    </form>
</div>
@endsection
