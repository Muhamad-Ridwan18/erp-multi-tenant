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

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif

    <input
        type="{{ $type }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($id) id="{{ $id }}" @endif
        {{ $attributes->merge([
            'class' => 'form-control'.($error ? ' is-invalid' : ''),
        ]) }}
    >

    @if ($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @elseif ($help)
        <div class="form-hint">{{ $help }}</div>
    @endif
</div>
