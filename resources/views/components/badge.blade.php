@props([
    'tone' => 'neutral',
])

@php
$classes = match ($tone) {
    'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
    'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
    'danger' => 'bg-red-50 text-red-800 border-red-200',
    'brand' => 'bg-ink-100 text-ink-800 border-ink-200',
    default => 'bg-ink-50 text-ink-600 border-line',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ $slot }}
</span>
