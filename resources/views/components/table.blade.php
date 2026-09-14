@props([
    'headers' => [],
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-line bg-panel shadow-sm']) }}>
    <table class="w-full text-sm">
        @if (count($headers))
            <thead class="bg-ink-50 text-left text-ink-600">
                <tr>
                    @foreach ($headers as $header)
                        <th class="px-4 py-3 font-medium">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-line">
            {{ $slot }}
        </tbody>
    </table>
</div>
