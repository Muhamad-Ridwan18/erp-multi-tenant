<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Daksa ERP')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    @auth
        @php
            $user = auth()->user();
            $isTenantHost = \App\Support\TenantContext::check();
        @endphp

        <div class="min-h-screen lg:grid lg:grid-cols-[16rem_1fr]">
            <aside class="border-b border-line bg-ink-950 text-ink-50 lg:border-b-0 lg:border-r lg:min-h-screen">
                <div class="px-5 py-5">
                    <a href="{{ $isTenantHost ? route('dashboard') : route('platform.tenants.index') }}" class="block">
                        <div class="text-lg font-semibold tracking-tight text-white">Daksa</div>
                        <div class="text-xs text-ink-300">ERP multi-tenant</div>
                    </a>
                </div>

                <nav class="space-y-1 px-3 pb-6 text-sm">
                    @if ($isTenantHost)
                        <a href="{{ route('dashboard') }}"
                           class="block rounded-lg px-3 py-2 {{ request()->routeIs('dashboard') ? 'bg-ink-800 text-white' : 'text-ink-200 hover:bg-ink-900 hover:text-white' }}">
                            Dashboard
                        </a>
                        @can('partners.customers.view')
                            <a href="{{ route('tenant.customers.index') }}"
                               class="block rounded-lg px-3 py-2 {{ request()->routeIs('tenant.customers.*') ? 'bg-ink-800 text-white' : 'text-ink-200 hover:bg-ink-900 hover:text-white' }}">
                                Customers
                            </a>
                        @endcan
                        @can('inventory.products.view')
                            <a href="{{ route('tenant.products.index') }}"
                               class="block rounded-lg px-3 py-2 {{ request()->routeIs('tenant.products.*') ? 'bg-ink-800 text-white' : 'text-ink-200 hover:bg-ink-900 hover:text-white' }}">
                                Products
                            </a>
                        @endcan
                        @can('sales.orders.view')
                            <a href="{{ route('tenant.orders.index') }}"
                               class="block rounded-lg px-3 py-2 {{ request()->routeIs('tenant.orders.*') ? 'bg-ink-800 text-white' : 'text-ink-200 hover:bg-ink-900 hover:text-white' }}">
                                Sales orders
                            </a>
                        @endcan
                        @can('settings.users.view')
                            <a href="{{ route('tenant.users.index') }}"
                               class="block rounded-lg px-3 py-2 {{ request()->routeIs('tenant.users.*') ? 'bg-ink-800 text-white' : 'text-ink-200 hover:bg-ink-900 hover:text-white' }}">
                                Users
                            </a>
                        @endcan
                        @can('settings.roles.view')
                            <a href="{{ route('tenant.roles.index') }}"
                               class="block rounded-lg px-3 py-2 {{ request()->routeIs('tenant.roles.*') ? 'bg-ink-800 text-white' : 'text-ink-200 hover:bg-ink-900 hover:text-white' }}">
                                Roles
                            </a>
                        @endcan
                    @else
                        <a href="{{ route('platform.tenants.index') }}"
                           class="block rounded-lg px-3 py-2 {{ request()->routeIs('platform.tenants.*') ? 'bg-ink-800 text-white' : 'text-ink-200 hover:bg-ink-900 hover:text-white' }}">
                            Tenants
                        </a>
                    @endif
                </nav>

                @if ($isTenantHost)
                    <div class="mt-auto hidden border-t border-ink-800 px-5 py-4 text-xs text-ink-300 lg:block">
                        <div class="font-medium text-ink-100">{{ \App\Support\TenantContext::get()?->name }}</div>
                        <div>{{ \App\Support\TenantContext::get()?->status }}</div>
                    </div>
                @endif
            </aside>

            <div class="min-w-0">
                <header class="flex items-center justify-between gap-4 border-b border-line bg-panel px-4 py-3 sm:px-6">
                    <div>
                        <div class="text-sm font-medium text-ink-900">@yield('page-title', 'Workspace')</div>
                        @hasSection('page-subtitle')
                            <div class="text-xs text-ink-500">@yield('page-subtitle')</div>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="hidden text-ink-600 sm:inline">{{ $user->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-button type="submit" variant="ghost">Logout</x-button>
                        </form>
                    </div>
                </header>

                <main class="px-4 py-6 sm:px-6">
                    @if (session('status'))
                        <x-alert class="mb-4">{{ session('status') }}</x-alert>
                    @endif

                    @if ($errors->any())
                        <x-alert type="error" class="mb-4">
                            <ul class="list-disc pl-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    @else
        <main class="min-h-screen">
            @yield('content')
        </main>
    @endauth
</body>
</html>
