@props([
    'type' => 'customer', // customer|vendor
    'storeUrl',
    'selectId',
    'canCreate' => false,
])

@if ($canCreate)
    <div class="mt-1.5">
        <button
            type="button"
            class="text-xs font-medium text-ink-600 underline hover:text-ink-900"
            data-open-quick-create
            data-quick-type="{{ $type }}"
            data-quick-url="{{ $storeUrl }}"
            data-quick-select="{{ $selectId }}"
        >
            + Create {{ $type }}
        </button>
    </div>
@endif
