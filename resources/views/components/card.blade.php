@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($padding)
        <div class="card-body">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
