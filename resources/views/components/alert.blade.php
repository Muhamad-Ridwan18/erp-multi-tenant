@props([
    'type' => 'success',
])

@php
$classes = match ($type) {
    'error' => 'alert-danger',
    'info' => 'alert-info',
    'warning' => 'alert-warning',
    default => 'alert-success',
};
@endphp

<div {{ $attributes->merge(['class' => "alert {$classes}", 'role' => 'alert']) }}>
    {{ $slot }}
</div>
