@extends('layouts.app')

@section('title', 'Aging')
@section('page-title', 'AR / AP aging')
@section('page-subtitle', 'Finance')

@section('content')
    <div class="mb-3 btn-list">
        <a href="{{ route('tenant.aging.index', ['type' => 'ar']) }}" class="btn {{ $type === 'ar' ? 'btn-primary' : 'btn-ghost-secondary' }}">Receivables</a>
        <a href="{{ route('tenant.aging.index', ['type' => 'ap']) }}" class="btn {{ $type === 'ap' ? 'btn-primary' : 'btn-ghost-secondary' }}">Payables</a>
    </div>

    <div class="row g-3 mb-3">
        @foreach ([
            'current' => 'Current',
            '1_30' => '1–30',
            '31_60' => '31–60',
            '61_90' => '61–90',
            '90_plus' => '90+',
        ] as $key => $label)
            <div class="col">
                <x-card>
                    <div class="text-secondary text-uppercase small">{{ $label }}</div>
                    <div class="fw-bold fs-4">Rp {{ number_format($report['buckets'][$key] ?? 0, 0, ',', '.') }}</div>
                </x-card>
            </div>
        @endforeach
    </div>

    <x-table :headers="['Document', 'Partner', 'Due', 'Residual', 'Bucket', 'Days']" :title="'As of '.$report['as_of']">
        @forelse ($report['rows'] as $row)
            <tr>
                <td class="font-monospace small">{{ $row['document'] }}</td>
                <td>{{ $row['partner'] }}</td>
                <td>{{ $row['due'] }}</td>
                <td>Rp {{ number_format($row['residual'], 0, ',', '.') }}</td>
                <td>{{ $row['bucket'] }}</td>
                <td>{{ $row['days_overdue'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary py-4">Nothing outstanding.</td></tr>
        @endforelse
    </x-table>
@endsection
