@props([
    'headers' => [],
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            @if (count($headers))
                <thead>
                    <tr>
                        @foreach ($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
