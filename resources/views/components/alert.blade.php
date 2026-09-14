@props([
    'type' => 'success',
])

@php
$classes = match ($type) {
    'error' => 'bg-red-50 text-red-800 border-red-200',
    'info' => 'bg-sky-50 text-sky-800 border-sky-200',
    default => 'bg-emerald-50 text-emerald-800 border-emerald-200',
};
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border px-4 py-3 text-sm {$classes}"]) }}>
    {{ $slot }}
</div>
