@props([
    'label' => null,
    'name' => null,
    'help' => null,
    'searchable' => true,
    'placeholder' => 'Select…',
])

@php
    $id = $attributes->get('id') ?? $name;
    $error = $name ? $errors->first($name) : null;
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif

    <select
        @if ($name) name="{{ $name }}" @endif
        @if ($id) id="{{ $id }}" @endif
        @if ($searchable) data-tom-select @endif
        data-placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'form-select'.($error ? ' is-invalid' : ''),
        ]) }}
    >
        {{ $slot }}
    </select>

    @if ($error)
        <div class="invalid-feedback d-block">{{ $error }}</div>
    @elseif ($help)
        <div class="form-hint">{{ $help }}</div>
    @endif
</div>
