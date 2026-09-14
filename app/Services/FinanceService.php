<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinanceService
{
    public function createInvoiceFromSalesOrder(SalesOrder $order, User $user): Invoice
    {
        if (! $order->isConfirmed()) {
            throw new InvalidArgumentException('Only confirmed sales orders can be invoiced.');
        }

        if (Invoice::query()->where('sales_order_id', $order->id)->exists()) {
            throw new InvalidArgumentException('Sales order already has an invoice.');
        }

        $order->load('items.product');

        return DB::connection('tenant')->transaction(function () use ($order, $user) {
            $invoice = Invoice::query()->create([
                'number' => $this->nextNumber('INV'),
                'customer_id' => $order->customer_id,
                'sales_order_id' => $order->id,
                'status' => 'draft',
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total ?? 0,
                'tax_total' => $order->tax_total ?? 0,
                'grand_total' => $order->grand_total ?: $order->subtotal,
                'amount_paid' => 0,
                'notes' => $order->notes,
                'terms' => $order->terms,
                'created_by' => $user->id,
            ]);

            foreach ($order->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->product?->name ?? 'Item',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_percent' => $item->discount_percent ?? 0,
                    'tax_percent' => $item->tax_percent ?? 0,
                    'line_total' => $item->line_total,
                ]);
            }

            return $invoice->fresh(['customer', 'items', 'salesOrder']);
        });
    }

    public function createBillFromPurchaseOrder(PurchaseOrder $order, User $user): Bill
    {
        if (! $order->isReceived()) {
            throw new InvalidArgumentException('Only received purchase orders can be billed.');
        }

        if (Bill::query()->where('purchase_order_id', $order->id)->exists()) {
            throw new InvalidArgumentException('Purchase order already has a bill.');
        }

        $order->load('items.product');

        return DB::connection('tenant')->transaction(function () use ($order, $user) {
            $bill = Bill::query()->create([
                'number' => $this->nextNumber('BILL'),
                'vendor_id' => $order->vendor_id,
                'purchase_order_id' => $order->id,
                'status' => 'draft',
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total ?? 0,
                'tax_total' => $order->tax_total ?? 0,
                'grand_total' => $order->grand_total ?: $order->subtotal,
                'amount_paid' => 0,
                'notes' => $order->notes,
                'terms' => $order->terms,
                'created_by' => $user->id,
            ]);

            foreach ($order->items as $item) {
                $bill->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->product?->name ?? 'Item',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_percent' => $item->discount_percent ?? 0,
                    'tax_percent' => $item->tax_percent ?? 0,
                    'line_total' => $item->line_total,
                ]);
            }

            return $bill->fresh(['vendor', 'items', 'purchaseOrder']);
        });
    }

    public function postInvoice(Invoice $invoice): Invoice
    {
        if (! $invoice->isDraft()) {
            throw new InvalidArgumentException('Only draft invoices can be posted.');
        }

        if ($invoice->items()->doesntExist()) {
            throw new InvalidArgumentException('Invoice has no items.');
        }

        $invoice->update([
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        return $invoice->fresh();
    }

    public function postBill(Bill $bill): Bill
    {
        if (! $bill->isDraft()) {
            throw new InvalidArgumentException('Only draft bills can be posted.');
        }

        if ($bill->items()->doesntExist()) {
            throw new InvalidArgumentException('Bill has no items.');
        }

        $bill->update([
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        return $bill->fresh();
    }

    public function recordInvoicePayment(Invoice $invoice, int $amount, User $user, ?string $notes = null): Payment
    {
        if (! $invoice->isPosted()) {
            throw new InvalidArgumentException('Only posted invoices can receive payments.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive.');
        }

        if ($amount > $invoice->amountDue()) {
            throw new InvalidArgumentException('Payment exceeds amount due.');
        }

        return DB::connection('tenant')->transaction(function () use ($invoice, $amount, $user, $notes) {
            $payment = Payment::query()->create([
                'number' => $this->nextNumber('PAY'),
                'direction' => 'incoming',
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'paid_at' => now(),
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            $invoice->increment('amount_paid', $amount);

            return $payment;
        });
    }

    public function recordBillPayment(Bill $bill, int $amount, User $user, ?string $notes = null): Payment
    {
        if (! $bill->isPosted()) {
            throw new InvalidArgumentException('Only posted bills can receive payments.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive.');
        }

        if ($amount > $bill->amountDue()) {
            throw new InvalidArgumentException('Payment exceeds amount due.');
        }

        return DB::connection('tenant')->transaction(function () use ($bill, $amount, $user, $notes) {
            $payment = Payment::query()->create([
                'number' => $this->nextNumber('PAY'),
                'direction' => 'outgoing',
                'bill_id' => $bill->id,
                'amount' => $amount,
                'paid_at' => now(),
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            $bill->increment('amount_paid', $amount);

            return $payment;
        });
    }

    public function nextNumber(string $prefix): string
    {
        $fullPrefix = $prefix.'-'.now()->format('Ymd').'-';

        $latest = match ($prefix) {
            'INV' => Invoice::query()->where('number', 'like', $fullPrefix.'%')->orderByDesc('number')->value('number'),
            'BILL' => Bill::query()->where('number', 'like', $fullPrefix.'%')->orderByDesc('number')->value('number'),
            default => Payment::query()->where('number', 'like', $fullPrefix.'%')->orderByDesc('number')->value('number'),
        };

        $seq = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return $fullPrefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
