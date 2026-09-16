@extends('layouts.app')

@section('title', $statement->name)
@section('page-title', 'Bank statement')
@section('page-subtitle', $statement->name)

@section('content')
    <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <x-badge :tone="$statement->status === 'done' ? 'success' : 'warning'">{{ $statement->status }}</x-badge>
            <span class="text-secondary">{{ $statement->journal?->code }} · {{ optional($statement->date)->format('Y-m-d') }}</span>
            @if ($statement->reference)
                <span class="small text-secondary">{{ $statement->reference }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($statement->isOpen())
                @can('finance.bank_statements.reconcile')
                    <form method="POST" action="{{ route('tenant.bank-statements.complete', $statement) }}" onsubmit="return confirm('Mark statement as done? All lines must be matched.')">
                        @csrf
                        <x-button type="submit">Complete</x-button>
                    </form>
                @endcan
            @endif
            <x-button href="{{ route('tenant.bank-statements.index') }}" variant="ghost">Back</x-button>
        </div>
    </div>

    @error('statement')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
    @error('payment_id')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
    @error('line')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Start balance</div>
                <div class="fw-bold fs-4">Rp {{ number_format($statement->balance_start, 0, ',', '.') }}</div>
            </x-card>
        </div>
        <div class="col-md-4">
            <x-card>
                <div class="text-secondary text-uppercase small">End balance</div>
                <div class="fw-bold fs-4">Rp {{ number_format($statement->balance_end, 0, ',', '.') }}</div>
            </x-card>
        </div>
        <div class="col-md-4">
            <x-card>
                <div class="text-secondary text-uppercase small">Reconciled</div>
                <div class="fw-bold fs-4">{{ $statement->lines->where('is_reconciled', true)->count() }} / {{ $statement->lines->count() }}</div>
            </x-card>
        </div>
    </div>

    <x-table :headers="['Date', 'Partner / label', 'Amount', 'Match', '']" title="Lines">
        @forelse ($statement->lines as $line)
            <tr>
                <td>{{ optional($line->date)->format('Y-m-d') ?? '—' }}</td>
                <td>
                    <div class="fw-medium">{{ $line->partner_name ?: '—' }}</div>
                    <div class="small text-secondary">{{ $line->label }}</div>
                    @if ($line->payment_reference)
                        <div class="small font-monospace text-secondary">{{ $line->payment_reference }}</div>
                    @endif
                </td>
                <td class="font-monospace {{ $line->amount >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($line->amount, 0, ',', '.') }}
                </td>
                <td>
                    @if ($line->is_reconciled)
                        <x-badge tone="success">matched</x-badge>
                        @if ($line->payment)
                            <div class="small mt-1">{{ $line->payment->number }}</div>
                        @endif
                    @else
                        <x-badge tone="warning">open</x-badge>
                    @endif
                </td>
                <td class="text-end" style="min-width: 16rem">
                    @if ($statement->isOpen())
                        @can('finance.bank_statements.reconcile')
                            @if ($line->is_reconciled)
                                <form method="POST" action="{{ route('tenant.bank-statements.unmatch', [$statement, $line]) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-ghost-secondary">Unmatch</button>
                                </form>
                            @else
                                @php $opts = $suggestions[$line->id] ?? collect(); @endphp
                                @if ($opts->isNotEmpty())
                                    <form method="POST" action="{{ route('tenant.bank-statements.match', [$statement, $line]) }}" class="d-inline-flex gap-1 justify-content-end">
                                        @csrf
                                        <select name="payment_id" class="form-select form-select-sm" required>
                                            @foreach ($opts as $payment)
                                                <option value="{{ $payment->id }}">
                                                    {{ $payment->number }} · Rp {{ number_format($payment->amount_company ?: $payment->amount, 0, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-primary">Match</button>
                                    </form>
                                @else
                                    <span class="small text-secondary">No matching payments</span>
                                @endif
                            @endif
                        @endcan
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-secondary py-4">No lines.</td></tr>
        @endforelse
    </x-table>
@endsection
