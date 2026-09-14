<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Daksa ERP')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                            <div class="d-flex align-items-center gap-3">
                                <span class="d-none d-sm-inline text-secondary">{{ $user->name }}</span>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-button type="submit" variant="ghost">Logout</x-button>
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
            @yield('content')
        </div>
    </div>
@endauth
</body>
</html>
