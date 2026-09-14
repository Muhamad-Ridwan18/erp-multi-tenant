<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Daksa ERP')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen">
    @auth
        <header class="bg-white border-b border-slate-200">
            <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <a href="{{ auth()->user()->isPlatformAdmin() ? route('platform.tenants.index') : route('dashboard') }}" class="font-semibold tracking-tight">Daksa ERP</a>
                    @unless(auth()->user()->isPlatformAdmin())
                        <nav class="flex gap-3 text-sm">
                            <a href="{{ route('dashboard') }}" class="text-slate-600 hover:text-slate-900">Dashboard</a>
                            @can('settings.roles.view')
                                <a href="{{ route('tenant.roles.index') }}" class="text-slate-600 hover:text-slate-900">Roles</a>
                            @endcan
                        </nav>
                    @else
                        <nav class="flex gap-3 text-sm">
                            <a href="{{ route('platform.tenants.index') }}" class="text-slate-600 hover:text-slate-900">Tenants</a>
                        </nav>
                    @endunless
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-slate-500">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-slate-700 underline">Logout</button>
                    </form>
                </div>
            </div>
        </header>
    @endauth

    <main class="max-w-5xl mx-auto px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 px-4 py-3 text-sm">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 text-red-800 px-4 py-3 text-sm">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
