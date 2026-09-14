@props([
    'tone' => 'neutral',
])

@php
$classes = match ($tone) {
    'success' => 'bg-success-lt',
    'warning' => 'bg-warning-lt',
    'danger' => 'bg-danger-lt',
    'brand' => 'bg-primary-lt',
    default => 'bg-secondary-lt',
};
@endphp

<span {{ $attributes->merge(['class' => "badge {$classes}"]) }}>
    {{ $slot }}
</span>
