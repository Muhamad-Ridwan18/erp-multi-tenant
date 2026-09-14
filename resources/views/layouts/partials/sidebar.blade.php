@php
    $user = auth()->user();
    $isTenantHost = \App\Support\TenantContext::check();
    $home = $isTenantHost ? route('dashboard') : route('platform.tenants.index');
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-brand navbar-brand-autodark">
            <a href="{{ $home }}" class="d-flex flex-column">
                <span class="navbar-brand-text">Daksa</span>
                <span class="text-secondary small">ERP multi-tenant</span>
            </a>
        </div>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @if ($isTenantHost)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <span class="nav-link-title">Dashboard</span>
                        </a>
                    </li>

                    @canany(['procurement.vendors.view', 'procurement.orders.view'])
                        <li class="nav-item mt-2">
                            <span class="nav-link disabled text-uppercase small opacity-50">Procurement</span>
                        </li>
                    @endcanany
                    @can('procurement.vendors.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.vendors.*') ? 'active' : '' }}" href="{{ route('tenant.vendors.index') }}">
                                <span class="nav-link-title">Vendors</span>
                            </a>
                        </li>
                    @endcan
                    @can('procurement.orders.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.purchases.*') ? 'active' : '' }}" href="{{ route('tenant.purchases.index') }}">
                                <span class="nav-link-title">Purchase orders</span>
                            </a>
                        </li>
                    @endcan

                    @canany(['sales.customers.view', 'sales.orders.view', 'inventory.products.view'])
                        <li class="nav-item mt-2">
                            <span class="nav-link disabled text-uppercase small opacity-50">Sales &amp; Inventory</span>
                        </li>
                    @endcanany
                    @can('sales.customers.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.customers.*') ? 'active' : '' }}" href="{{ route('tenant.customers.index') }}">
                                <span class="nav-link-title">Customers</span>
                            </a>
                        </li>
                    @endcan
                    @can('inventory.products.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.products.*') ? 'active' : '' }}" href="{{ route('tenant.products.index') }}">
                                <span class="nav-link-title">Products</span>
                            </a>
                        </li>
                    @endcan
                    @can('sales.orders.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.orders.*') ? 'active' : '' }}" href="{{ route('tenant.orders.index') }}">
                                <span class="nav-link-title">Sales orders</span>
                            </a>
                        </li>
                    @endcan

                    @canany(['finance.invoices.view', 'finance.bills.view'])
                        <li class="nav-item mt-2">
                            <span class="nav-link disabled text-uppercase small opacity-50">Finance</span>
                        </li>
                    @endcanany
                    @can('finance.invoices.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.invoices.*') ? 'active' : '' }}" href="{{ route('tenant.invoices.index') }}">
                                <span class="nav-link-title">Invoices</span>
                            </a>
                        </li>
                    @endcan
                    @can('finance.bills.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.bills.*') ? 'active' : '' }}" href="{{ route('tenant.bills.index') }}">
                                <span class="nav-link-title">Bills</span>
                            </a>
                        </li>
                    @endcan

                    @canany(['settings.users.view', 'settings.roles.view'])
                        <li class="nav-item mt-2">
                            <span class="nav-link disabled text-uppercase small opacity-50">Settings</span>
                        </li>
                    @endcanany
                    @can('settings.users.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.users.*') ? 'active' : '' }}" href="{{ route('tenant.users.index') }}">
                                <span class="nav-link-title">Users</span>
                            </a>
                        </li>
                    @endcan
                    @can('settings.roles.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.roles.*') ? 'active' : '' }}" href="{{ route('tenant.roles.index') }}">
                                <span class="nav-link-title">Roles</span>
                            </a>
                        </li>
                    @endcan

                    @if ($isTenantHost)
                        <li class="nav-item mt-4 px-3">
                            <div class="small text-secondary">{{ \App\Support\TenantContext::get()?->name }}</div>
                            <div class="small text-secondary opacity-75">{{ \App\Support\TenantContext::get()?->status }}</div>
                        </li>
                    @endif
                @else
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('platform.tenants.*') ? 'active' : '' }}" href="{{ route('platform.tenants.index') }}">
                            <span class="nav-link-title">Tenants</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</aside>
