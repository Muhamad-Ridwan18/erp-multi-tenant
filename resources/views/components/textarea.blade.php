@props([
    'label' => null,
    'name' => null,
    'help' => null,
    'rows' => 3,
])

@php
    $id = $attributes->get('id') ?? $name;
    $error = $name ? $errors->first($name) : null;
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif

    <textarea
        @if ($name) name="{{ $name }}" @endif
        @if ($id) id="{{ $id }}" @endif
        rows="{{ $rows }}"
        {{ $attributes->merge([
            'class' => 'form-control'.($error ? ' is-invalid' : ''),
        ]) }}
    >{{ $slot }}</textarea>

    @if ($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @elseif ($help)
        <div class="form-hint">{{ $help }}</div>
    @endif
</div>
