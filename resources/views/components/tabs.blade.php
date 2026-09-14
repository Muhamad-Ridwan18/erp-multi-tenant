@props([
    'tabs' => [],
])

@php
    $tabKeys = array_keys($tabs);
    $first = $tabKeys[0] ?? 'main';
@endphp

<div data-tabs>
    <div class="mb-4 flex flex-wrap gap-1 border-b border-line" role="tablist">
        @foreach ($tabs as $key => $label)
            <button
                type="button"
                role="tab"
                data-tab-trigger="{{ $key }}"
                aria-selected="{{ $key === $first ? 'true' : 'false' }}"
                @class([
                    'border-b-2 px-4 py-2 text-sm font-medium transition',
                    'border-ink-800 text-ink-950' => $key === $first,
                    'border-transparent text-ink-500 hover:text-ink-800' => $key !== $first,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{ $slot }}
</div>
