@extends('layouts.app')

@section('title', 'Bank reconciliation')
@section('page-title', 'Bank statements')
@section('page-subtitle', 'Finance')

@section('content')
    <div class="mb-3 d-flex justify-content-end">
        @can('finance.bank_statements.manage')
            <x-button href="{{ route('tenant.bank-statements.create') }}">Import statement</x-button>
        @endcan
    </div>

    <x-table :headers="['Name', 'Journal', 'Date', 'Lines', 'Status', '']" title="Statements">
        @forelse ($statements as $statement)
            <tr>
                <td class="fw-medium">
                    <a href="{{ route('tenant.bank-statements.show', $statement) }}">{{ $statement->name }}</a>
                    @if ($statement->reference)
                        <div class="small text-secondary">{{ $statement->reference }}</div>
                    @endif
                </td>
                <td class="text-secondary">{{ $statement->journal?->code ?? '—' }}</td>
                <td>{{ optional($statement->date)->format('Y-m-d') ?? '—' }}</td>
                <td>{{ $statement->lines_count }}</td>
                <td>
                    <x-badge :tone="$statement->status === 'done' ? 'success' : 'warning'">{{ $statement->status }}</x-badge>
                </td>
                <td class="text-end">
                    <a href="{{ route('tenant.bank-statements.show', $statement) }}" class="btn btn-sm btn-ghost-secondary">Open</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-secondary py-4">No bank statements yet.</td></tr>
        @endforelse
    </x-table>
@endsection
