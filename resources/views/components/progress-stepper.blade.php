@props([
    'steps' => [],
    'current' => null,
])

@php
    $stepKeys = array_keys($steps);
    $currentIndex = array_search($current, $stepKeys, true);
    if ($currentIndex === false) {
        $currentIndex = 0;
    }
@endphp

<div class="mb-3">
    <ul class="steps steps-counter steps-green">
        @foreach ($steps as $key => $label)
            @php
                $index = array_search($key, $stepKeys, true);
                $done = $index < $currentIndex;
                $active = $index === $currentIndex;
            @endphp
            <li @class([
                'step-item',
                'active' => $active || $done,
            ])>
                {{ $label }}
            </li>
        @endforeach
    </ul>
</div>
