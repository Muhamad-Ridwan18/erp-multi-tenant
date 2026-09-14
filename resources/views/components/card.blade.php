@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-panel shadow-sm'.($padding ? ' p-6' : '')]) }}>
    {{ $slot }}
</div>
