@props([
    'type' => 'customer',
    'storeUrl',
    'selectId',
    'canCreate' => false,
])

@if ($canCreate)
    <div class="mt-n2 mb-3">
        <button
            type="button"
            class="btn btn-link btn-sm px-0"
            data-open-quick-create
            data-quick-type="{{ $type }}"
            data-quick-url="{{ $storeUrl }}"
            data-quick-select="{{ $selectId }}"
        >
            + Create {{ $type }}
        </button>
    </div>
@endif
