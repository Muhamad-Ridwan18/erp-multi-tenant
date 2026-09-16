@extends('layouts.app')

@section('title', $entry->number)
@section('page-title', 'Journal entry')
@section('page-subtitle', $entry->number)

@section('content')
    <div class="mb-3 d-flex justify-content-between">
        <div>
            <div class="text-secondary">{{ $entry->journal?->name }} · {{ $entry->date?->format('d M Y') }}</div>
            <div class="fw-medium">{{ $entry->narration ?: $entry->reference }}</div>
        </div>
        <x-button href="{{ route('tenant.journal-entries.index') }}" variant="ghost">Back</x-button>
    </div>

    <x-table :headers="['Account', 'Label', 'Debit', 'Credit']">
        @foreach ($entry->items as $item)
            <tr>
                <td class="font-monospace small">{{ $item->account?->code }} {{ $item->account?->name }}</td>
                <td>{{ $item->label }}</td>
                <td>{{ $item->debit ? 'Rp '.number_format($item->debit, 0, ',', '.') : '—' }}</td>
                <td>{{ $item->credit ? 'Rp '.number_format($item->credit, 0, ',', '.') : '—' }}</td>
            </tr>
        @endforeach
    </x-table>
@endsection
