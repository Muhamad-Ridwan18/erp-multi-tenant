@props([
    'products',
    'items' => null,
    'showStock' => false,
])

@php
    $items = $items ?? old('items', [[
        'product_id' => '',
        'quantity' => 1,
        'unit_price' => 0,
        'discount_percent' => 0,
        'tax_percent' => 0,
    ]]);
@endphp

<div class="card" data-document-lines>
    <div class="card-header">
        <h3 class="card-title">Order lines</h3>
        <div class="card-actions">
            <button type="button" class="btn btn-sm btn-primary" data-add-line>+ Add a line</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table document-lines-table mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="width: 5.5rem">Qty</th>
                    <th style="width: 7rem">Unit price</th>
                    <th style="width: 5rem">Disc %</th>
                    <th style="width: 5rem">Tax %</th>
                    <th class="text-end" style="width: 7rem">Amount</th>
                    <th style="width: 2.5rem"></th>
                </tr>
            </thead>
            <tbody data-lines>
                @foreach ($items as $i => $item)
                    @include('components.partials.document-line-row', [
                        'index' => $i,
                        'item' => $item,
                        'products' => $products,
                        'showStock' => $showStock,
                    ])
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-end">
            <div style="min-width: 16rem">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-secondary">Untaxed</span>
                    <span data-doc-subtotal>Rp 0</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-secondary">Discount</span>
                    <span data-doc-discount>Rp 0</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-secondary">Tax</span>
                    <span data-doc-tax>Rp 0</span>
                </div>
                <div class="d-flex justify-content-between fw-bold border-top pt-2">
                    <span>Total</span>
                    <span data-doc-total>Rp 0</span>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="line-template">
    @include('components.partials.document-line-row', [
        'index' => '__INDEX__',
        'item' => ['product_id' => '', 'quantity' => 1, 'unit_price' => 0, 'discount_percent' => 0, 'tax_percent' => 0],
        'products' => $products,
        'showStock' => $showStock,
    ])
</template>
