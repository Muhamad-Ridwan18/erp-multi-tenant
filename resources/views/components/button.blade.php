@props([
    'variant' => 'primary',
    'type' => 'submit',
    'href' => null,
])

@php
$classes = match ($variant) {
    'secondary' => 'bg-white text-ink-800 border border-line hover:bg-ink-50',
    'danger' => 'bg-red-700 text-white hover:bg-red-800',
    'ghost' => 'bg-transparent text-ink-600 hover:bg-ink-100',
    default => 'bg-ink-800 text-white hover:bg-ink-900',
};
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium transition {$classes}"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium transition {$classes}"]) }}>
        {{ $slot }}
    </button>
@endif
