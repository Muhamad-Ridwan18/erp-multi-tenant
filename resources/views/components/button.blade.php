@props([
    'variant' => 'primary',
    'type' => 'submit',
    'href' => null,
])

@php
$classes = match ($variant) {
    'secondary' => 'btn-outline-secondary',
    'danger' => 'btn-danger',
    'ghost' => 'btn-ghost-secondary',
    default => 'btn-primary',
};
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "btn {$classes}"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "btn {$classes}"]) }}>
        {{ $slot }}
    </button>
@endif
