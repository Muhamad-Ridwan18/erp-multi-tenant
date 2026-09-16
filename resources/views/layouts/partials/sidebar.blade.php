@php
    $isTenantHost = \App\Support\TenantContext::check();
    $home = $isTenantHost ? route('dashboard') : route('platform.tenants.index');
    $tenant = $isTenantHost ? \App\Support\TenantContext::get() : null;
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-brand navbar-brand-autodark">
            <a href="{{ $home }}" class="d-flex align-items-center gap-2 text-decoration-none">
                <span class="avatar avatar-sm bg-primary text-white fw-bold" style="--tblr-avatar-font-size: .85rem">D</span>
                <span>
                    <span class="navbar-brand-text d-block text-white">Daksa</span>
                    <span class="small text-secondary">ERP</span>
                </span>
            </a>
        </div>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @if ($isTenantHost)
                    <li class="nav-section-title">Overview</li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <span class="nav-link-icon"><i class="ti ti-layout-dashboard"></i></span>
                            <span class="nav-link-title">Dashboard</span>
                        </a>
                    </li>

                    @canany(['procurement.vendors.view', 'procurement.orders.view'])
                        <li class="nav-section-title">Procurement</li>
                    @endcanany
                    @can('procurement.vendors.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.vendors.*') ? 'active' : '' }}" href="{{ route('tenant.vendors.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-building-store"></i></span>
                                <span class="nav-link-title">Vendors</span>
                            </a>
                        </li>
                    @endcan
                    @can('procurement.orders.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.purchases.*') ? 'active' : '' }}" href="{{ route('tenant.purchases.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-truck-delivery"></i></span>
                                <span class="nav-link-title">Purchase orders</span>
                            </a>
                        </li>
                    @endcan

                    @canany(['sales.customers.view', 'sales.orders.view', 'inventory.products.view', 'inventory.warehouses.view', 'inventory.operations.view'])
                        <li class="nav-section-title">Sales &amp; Inventory</li>
                    @endcanany
                    @can('sales.customers.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.customers.*') ? 'active' : '' }}" href="{{ route('tenant.customers.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-users"></i></span>
                                <span class="nav-link-title">Customers</span>
                            </a>
                        </li>
                    @endcan
                    @can('inventory.products.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.products.*') ? 'active' : '' }}" href="{{ route('tenant.products.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-package"></i></span>
                                <span class="nav-link-title">Products</span>
                            </a>
                        </li>
                    @endcan
                    @can('sales.orders.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.orders.*') ? 'active' : '' }}" href="{{ route('tenant.orders.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-shopping-cart"></i></span>
                                <span class="nav-link-title">Sales orders</span>
                            </a>
                        </li>
                    @endcan
                    @can('inventory.warehouses.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.warehouses.*') ? 'active' : '' }}" href="{{ route('tenant.warehouses.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-building-warehouse"></i></span>
                                <span class="nav-link-title">Warehouses</span>
                            </a>
                        </li>
                    @endcan
                    @can('inventory.operations.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.operations.*') ? 'active' : '' }}" href="{{ route('tenant.operations.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-arrows-exchange"></i></span>
                                <span class="nav-link-title">Operations</span>
                            </a>
                        </li>
                    @endcan

                    @canany(['finance.invoices.view', 'finance.bills.view', 'finance.taxes.view'])
                        <li class="nav-section-title">Finance</li>
                    @endcanany
                    @can('finance.invoices.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.invoices.*') ? 'active' : '' }}" href="{{ route('tenant.invoices.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-file-invoice"></i></span>
                                <span class="nav-link-title">Invoices</span>
                            </a>
                        </li>
                    @endcan
                    @can('finance.bills.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.bills.*') ? 'active' : '' }}" href="{{ route('tenant.bills.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-receipt"></i></span>
                                <span class="nav-link-title">Bills</span>
                            </a>
                        </li>
                    @endcan
                    @can('finance.taxes.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.taxes.*') ? 'active' : '' }}" href="{{ route('tenant.taxes.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-percentage"></i></span>
                                <span class="nav-link-title">Taxes</span>
                            </a>
                        </li>
                    @endcan

                    @canany(['settings.users.view', 'settings.roles.view', 'settings.categories.manage'])
                        <li class="nav-section-title">Settings</li>
                    @endcanany
                    @can('settings.users.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.users.*') ? 'active' : '' }}" href="{{ route('tenant.users.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-user-cog"></i></span>
                                <span class="nav-link-title">Users</span>
                            </a>
                        </li>
                    @endcan
                    @can('settings.roles.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.roles.*') ? 'active' : '' }}" href="{{ route('tenant.roles.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-shield-lock"></i></span>
                                <span class="nav-link-title">Roles</span>
                            </a>
                        </li>
                    @endcan
                    @can('settings.categories.manage')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tenant.categories.*') ? 'active' : '' }}" href="{{ route('tenant.categories.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-category"></i></span>
                                <span class="nav-link-title">Product categories</span>
                            </a>
                        </li>
                    @endcan

                    @if ($tenant)
                        <li class="nav-item mt-3">
                            <div class="nav-link disabled">
                                <span class="nav-link-icon"><i class="ti ti-building"></i></span>
                                <span class="nav-link-title">
                                    {{ $tenant->name }}
                                    <div class="small text-secondary text-capitalize">{{ $tenant->status }}</div>
                                </span>
                            </div>
                        </li>
                    @endif
                @else
                    <li class="nav-section-title">Platform</li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('platform.tenants.*') ? 'active' : '' }}" href="{{ route('platform.tenants.index') }}">
                            <span class="nav-link-icon"><i class="ti ti-building-community"></i></span>
                            <span class="nav-link-title">Tenants</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</aside>
