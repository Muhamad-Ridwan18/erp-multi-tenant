@props([
    'headers' => [],
    'title' => null,
])

<div {{ $attributes->class(['card']) }}>
    @if ($title || isset($actions))
        <div class="card-header">
            @if ($title)
                <h3 class="card-title">{{ $title }}</h3>
            @endif
            @isset($actions)
                <div class="card-actions">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover mb-0">
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
