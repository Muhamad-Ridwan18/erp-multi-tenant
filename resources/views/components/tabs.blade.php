@props([
    'tabs' => [],
])

@php
    $tabKeys = array_keys($tabs);
    $first = $tabKeys[0] ?? 'main';
@endphp

<div data-tabs>
    <ul class="nav nav-tabs mb-3" role="tablist">
        @foreach ($tabs as $key => $label)
            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link {{ $key === $first ? 'active' : '' }}"
                    role="tab"
                    data-tab-trigger="{{ $key }}"
                    aria-selected="{{ $key === $first ? 'true' : 'false' }}"
                >
                    {{ $label }}
                </button>
            </li>
        @endforeach
    </ul>

    {{ $slot }}
</div>
