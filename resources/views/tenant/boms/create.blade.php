@extends('layouts.app')

@section('title', 'Create BOM')
@section('page-title', 'Create bill of materials')
@section('page-subtitle', 'Manufacturing')

@section('content')
    <form method="POST" action="{{ route('tenant.boms.store') }}" class="vstack gap-3" data-bom-form>
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <x-card>
                    <div class="row">
                        <div class="col-md-4">
                            <x-input label="Code" name="code" value="{{ old('code') }}" />
                        </div>
                        <div class="col-md-8">
                            <x-select label="Finished product" name="product_id" placeholder="Search product…" required>
                                <option value="">Select product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>
                                        {{ $product->name }} ({{ $product->sku }})
                                    </option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <x-input label="Produces qty" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required />
                        </div>
                        <div class="col-md-4">
                            <x-select label="UoM" name="uom_id" :searchable="false">
                                <option value="">Default</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}" @selected((string) old('uom_id') === (string) $uom->id)>{{ $uom->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="col-md-4">
                            <x-select label="Work center" name="work_center_id" placeholder="Optional…">
                                <option value="">None</option>
                                @foreach ($workCenters as $center)
                                    <option value="{{ $center->id }}" @selected((string) old('work_center_id') === (string) $center->id)>{{ $center->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <x-textarea label="Notes" name="notes" rows="2">{{ old('notes') }}</x-textarea>
                </x-card>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Components</h3>
                        <div class="card-actions">
                            <button type="button" class="btn btn-sm btn-primary" data-add-bom-line>+ Add component</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table mb-0">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th style="width: 8rem">Qty</th>
                                    <th style="width: 2.5rem"></th>
                                </tr>
                            </thead>
                            <tbody data-bom-lines>
                                @php $lines = old('lines', [['product_id' => '', 'quantity' => 1]]); @endphp
                                @foreach ($lines as $i => $line)
                                    <tr>
                                        <td>
                                            <select name="lines[{{ $i }}][product_id]" class="form-select" required data-tom-select>
                                                <option value="">Select…</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>
                                                        {{ $product->name }} ({{ $product->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="lines[{{ $i }}][quantity]" class="form-control" min="1" value="{{ $line['quantity'] ?? 1 }}" required>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-ghost-danger btn-icon" data-remove-bom-line>&times;</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Operations (routing)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Work center</th>
                                    <th style="width: 8rem">Minutes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($i = 0; $i < 2; $i++)
                                    <tr>
                                        <td>
                                            <input type="text" name="operations[{{ $i }}][name]" class="form-control" value="{{ old("operations.$i.name") }}" placeholder="e.g. Assemble">
                                        </td>
                                        <td>
                                            <select name="operations[{{ $i }}][work_center_id]" class="form-select">
                                                <option value="">Default</option>
                                                @foreach ($workCenters as $center)
                                                    <option value="{{ $center->id }}" @selected((string) old("operations.$i.work_center_id") === (string) $center->id)>{{ $center->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="operations[{{ $i }}][duration_minutes]" class="form-control" min="0" value="{{ old("operations.$i.duration_minutes", 0) }}">
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <x-card>
                    <x-button class="w-100">Save BOM</x-button>
                    <a href="{{ route('tenant.boms.index') }}" class="btn btn-ghost-secondary w-100 mt-2">Cancel</a>
                </x-card>
            </div>
        </div>
    </form>

    <template id="bom-line-template">
        <tr>
            <td>
                <select name="lines[__INDEX__][product_id]" class="form-select" required>
                    <option value="">Select…</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="lines[__INDEX__][quantity]" class="form-control" min="1" value="1" required>
            </td>
            <td>
                <button type="button" class="btn btn-ghost-danger btn-icon" data-remove-bom-line>&times;</button>
            </td>
        </tr>
    </template>

    @push('scripts')
        <script>
            (() => {
                const tbody = document.querySelector('[data-bom-lines]');
                const tpl = document.getElementById('bom-line-template');
                let idx = tbody.querySelectorAll('tr').length;
                document.querySelector('[data-add-bom-line]')?.addEventListener('click', () => {
                    const html = tpl.innerHTML.replaceAll('__INDEX__', String(idx++));
                    tbody.insertAdjacentHTML('beforeend', html);
                });
                tbody?.addEventListener('click', (e) => {
                    if (e.target.closest('[data-remove-bom-line]')) {
                        const rows = tbody.querySelectorAll('tr');
                        if (rows.length > 1) e.target.closest('tr').remove();
                    }
                });
            })();
        </script>
    @endpush
@endsection
