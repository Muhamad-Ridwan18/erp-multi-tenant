@extends('layouts.app')

@section('title', 'Edit tenant')
@section('page-title', 'Edit tenant')
@section('page-subtitle', $tenant->name)

@section('content')
    <div class="max-w-2xl">
        <h1 class="mb-6 text-2xl font-semibold text-ink-950">Edit tenant</h1>

        <form method="POST" action="{{ route('platform.tenants.update', $tenant) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-card class="space-y-4">
                <x-input label="Name" name="name" value="{{ old('name', $tenant->name) }}" required />
                <x-input label="Slug" name="slug" value="{{ old('slug', $tenant->slug) }}" required />
                <div class="space-y-1.5">
                    <label for="status" class="block text-sm font-medium text-ink-800">Status</label>
                    <select id="status" name="status" class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ink-400/40">
                        @foreach (['active', 'trial', 'suspended'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $tenant->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label for="plan_id" class="block text-sm font-medium text-ink-800">Plan</label>
                    <select id="plan_id" name="plan_id" required class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ink-400/40">
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('plan_id', $tenant->activeSubscription?->plan_id) === (string) $plan->id)>
                                {{ $plan->name }} — Rp {{ number_format($plan->price_monthly, 0, ',', '.') }}/mo
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-ink-500">Changing plan cancels the current active subscription and starts a new one.</p>
                </div>
            </x-card>

            <div class="flex items-center gap-3">
                <x-button>Save changes</x-button>
                <x-button href="{{ route('platform.tenants.show', $tenant) }}" variant="ghost">Cancel</x-button>
            </div>
        </form>
    </div>
@endsection
