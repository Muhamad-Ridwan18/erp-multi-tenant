@props([
    'name',
    'active' => false,
])

<div
    data-tab-panel="{{ $name }}"
    @class(['d-none' => ! $active])
>
    {{ $slot }}
</div>
