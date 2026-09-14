@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'help' => null,
])

@php
$id = $attributes->get('id') ?? $name;
$error = $name ? $errors->first($name) : null;
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-ink-800">{{ $label }}</label>
    @endif

    <input
        type="{{ $type }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($id) id="{{ $id }}" @endif
        {{ $attributes->merge([
            'class' => 'w-full rounded-lg border px-3 py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-2 focus:ring-ink-400/40 disabled:bg-ink-50 '.($error ? 'border-red-400' : 'border-line'),
        ]) }}
    >

    @if ($error)
        <p class="text-xs text-red-600">{{ $error }}</p>
    @elseif ($help)
        <p class="text-xs text-ink-500">{{ $help }}</p>
    @endif
</div>
