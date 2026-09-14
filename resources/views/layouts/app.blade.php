<!DOCTYPE html>
<html lang="en" data-bs-navbar-position="vertical">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Daksa ERP')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Apply theme before paint to avoid light flash (Tabler pattern). --}}
    <script>
        (function () {
            var stored = localStorage.getItem('tabler-theme');
            var theme = stored || 'dark';
            if (theme === 'auto') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', theme === 'dark' ? 'dark' : 'light');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@auth
    @php
        $user = auth()->user();
        $isTenantHost = \App\Support\TenantContext::check();
    @endphp

    <div class="page">
        @include('layouts.partials.sidebar')

        <div class="page-wrapper">
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <div class="page-pretitle">@yield('page-subtitle', $isTenantHost ? (\App\Support\TenantContext::get()?->name ?? 'Tenant') : 'Platform')</div>
                            <h2 class="page-title">@yield('page-title', 'Workspace')</h2>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                @yield('page-actions')

                                <a href="?theme=dark" class="btn btn-ghost-secondary btn-icon hide-theme-dark" data-theme-toggle="dark" title="Enable dark mode" aria-label="Enable dark mode">
                                    <i class="ti ti-moon"></i>
                                </a>
                                <a href="?theme=light" class="btn btn-ghost-secondary btn-icon hide-theme-light" data-theme-toggle="light" title="Enable light mode" aria-label="Enable light mode">
                                    <i class="ti ti-sun"></i>
                                </a>

                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost-secondary">
                                        <i class="ti ti-logout me-1"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-body">
                <div class="container-xl">
                    @if (session('status'))
                        <x-alert class="mb-3">{{ session('status') }}</x-alert>
                    @endif

                    @if ($errors->any())
                        <x-alert type="error" class="mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    @if ($isTenantHost)
        <x-quick-create-dialog />
    @endif
@else
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-end mb-3">
                <a href="?theme=dark" class="btn btn-ghost-secondary btn-icon hide-theme-dark" data-theme-toggle="dark" title="Enable dark mode" aria-label="Enable dark mode">
                    <i class="ti ti-moon"></i>
                </a>
                <a href="?theme=light" class="btn btn-ghost-secondary btn-icon hide-theme-light" data-theme-toggle="light" title="Enable light mode" aria-label="Enable light mode">
                    <i class="ti ti-sun"></i>
                </a>
            </div>
            @yield('content')
        </div>
    </div>
@endauth
</body>
</html>
