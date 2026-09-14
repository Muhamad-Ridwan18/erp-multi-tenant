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

<nav aria-label="Document progress" class="mb-6">
    <ol class="flex flex-wrap items-center gap-2">
        @foreach ($steps as $key => $label)
            @php
                $index = array_search($key, $stepKeys, true);
                $done = $index < $currentIndex;
                $active = $index === $currentIndex;
            @endphp
            <li class="flex items-center gap-2">
                <span @class([
                    'inline-flex h-8 items-center gap-2 rounded-full border px-3 text-xs font-medium',
                    'border-ink-800 bg-ink-800 text-white' => $active,
                    'border-emerald-200 bg-emerald-50 text-emerald-800' => $done,
                    'border-line bg-panel text-ink-500' => ! $active && ! $done,
                ])>
                    <span @class([
                        'inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px]',
                        'bg-white/20 text-white' => $active,
                        'bg-emerald-600 text-white' => $done,
                        'bg-ink-100 text-ink-600' => ! $active && ! $done,
                    ])>
                        {{ $done ? '✓' : $index + 1 }}
                    </span>
                    {{ $label }}
                </span>
                @if (! $loop->last)
                    <span class="hidden text-ink-300 sm:inline">→</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
