@extends('layouts.app')

@section('title', 'Import bank statement')
@section('page-title', 'Import bank statement')
@section('page-subtitle', 'Finance')

@section('content')
    <form method="POST" action="{{ route('tenant.bank-statements.store') }}" class="vstack gap-3" id="statement-form">
        @csrf
        <x-card>
            <div class="row">
                <div class="col-md-6">
                    <x-input label="Name" name="name" value="{{ old('name') }}" required />
                </div>
                <div class="col-md-6">
                    <x-input label="Reference" name="reference" value="{{ old('reference') }}" />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <x-select label="Journal" name="journal_id" placeholder="Search…">
                        <option value="">Default BANK</option>
                        @foreach ($journals as $journal)
                            <option value="{{ $journal->id }}" @selected((string) old('journal_id') === (string) $journal->id)>
                                {{ $journal->code }} — {{ $journal->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div class="col-md-4">
                    <x-input label="Statement date" name="date" type="date" value="{{ old('date', now()->toDateString()) }}" />
                </div>
                <div class="col-md-2">
                    <x-input label="Start balance" name="balance_start" type="number" value="{{ old('balance_start', 0) }}" />
                </div>
                <div class="col-md-2">
                    <x-input label="End balance" name="balance_end" type="number" value="{{ old('balance_end', 0) }}" />
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h3 mb-0">Lines</h2>
                <button type="button" class="btn btn-sm btn-ghost-secondary" id="add-line">Add line</button>
            </div>
            <div class="text-secondary small mb-2">Amounts in company currency (IDR). Use positive for inflow / credit, negative for outflow / debit.</div>
            <div class="table-responsive">
                <table class="table card-table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Partner</th>
                            <th>Label</th>
                            <th>Payment ref</th>
                            <th style="width: 9rem">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="lines-body">
                        @php $lines = old('lines', [['date' => now()->toDateString(), 'partner_name' => '', 'label' => '', 'payment_reference' => '', 'amount' => '']]); @endphp
                        @foreach ($lines as $i => $line)
                            <tr>
                                <td><input type="date" name="lines[{{ $i }}][date]" class="form-control" value="{{ $line['date'] ?? '' }}"></td>
                                <td><input type="text" name="lines[{{ $i }}][partner_name]" class="form-control" value="{{ $line['partner_name'] ?? '' }}"></td>
                                <td><input type="text" name="lines[{{ $i }}][label]" class="form-control" value="{{ $line['label'] ?? '' }}"></td>
                                <td><input type="text" name="lines[{{ $i }}][payment_reference]" class="form-control" value="{{ $line['payment_reference'] ?? '' }}"></td>
                                <td><input type="number" name="lines[{{ $i }}][amount]" class="form-control" value="{{ $line['amount'] ?? '' }}" required></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @error('lines')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
        </x-card>

        <div class="d-flex flex-wrap gap-2">
            <x-button>Create statement</x-button>
            <x-button href="{{ route('tenant.bank-statements.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>

    @push('scripts')
        <script>
            (() => {
                const body = document.getElementById('lines-body');
                const addBtn = document.getElementById('add-line');
                let index = body?.querySelectorAll('tr').length || 0;

                addBtn?.addEventListener('click', () => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><input type="date" name="lines[${index}][date]" class="form-control"></td>
                        <td><input type="text" name="lines[${index}][partner_name]" class="form-control"></td>
                        <td><input type="text" name="lines[${index}][label]" class="form-control"></td>
                        <td><input type="text" name="lines[${index}][payment_reference]" class="form-control"></td>
                        <td><input type="number" name="lines[${index}][amount]" class="form-control" required></td>
                    `;
                    body.appendChild(tr);
                    index += 1;
                });
            })();
        </script>
    @endpush
@endsection
