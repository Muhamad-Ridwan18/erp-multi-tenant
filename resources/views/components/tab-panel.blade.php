@props([
    'name',
    'active' => false,
])

<div
    data-tab-panel="{{ $name }}"
    @class(['space-y-4', 'hidden' => ! $active])
>
    {{ $slot }}
</div>
