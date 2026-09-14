@extends('layouts.app')

@section('title', 'Edit tenant')
@section('page-title', 'Edit tenant')
@section('page-subtitle', $tenant->name)

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('platform.tenants.update', $tenant) }}" class="vstack gap-3">
                @csrf
                @method('PUT')

                <x-card>
                    <x-input label="Name" name="name" value="{{ old('name', $tenant->name) }}" required />
                    <x-input label="Slug" name="slug" value="{{ old('slug', $tenant->slug) }}" required />
                    <x-select label="Status" name="status" :searchable="false">
                        @foreach (['active', 'trial', 'suspended'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $tenant->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </x-select>
                    <x-select label="Plan" name="plan_id" required :searchable="false" help="Changing plan cancels the current active subscription and starts a new one.">
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('plan_id', $tenant->activeSubscription?->plan_id) === (string) $plan->id)>
                                {{ $plan->name }} — Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}/mo
                            </option>
                        @endforeach
                    </x-select>
                </x-card>

                <div class="d-flex align-items-center gap-2">
                    <x-button>Save changes</x-button>
                    <x-button href="{{ route('platform.tenants.show', $tenant) }}" variant="ghost">Cancel</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
