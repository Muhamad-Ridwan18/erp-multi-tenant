<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Support\DocumentLineCalculator;
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

        $order->load('items.product', 'paymentTerm');

        return DB::connection('tenant')->transaction(function () use ($order, $user) {
            $invoiceDate = now()->toDateString();
            $due = $this->dueDate($invoiceDate, $order->paymentTerm);

            $invoice = Invoice::query()->create([
                'number' => $this->nextNumber('INV'),
                'customer_id' => $order->customer_id,
                'sales_order_id' => $order->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $due,
                'payment_term_id' => $order->payment_term_id,
                'journal_id' => Journal::query()->where('code', 'INV')->value('id'),
                'currency_id' => $order->currency_id,
                'status' => 'draft',
                'payment_state' => 'not_paid',
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
                    'uom_id' => $item->uom_id,
                    'description' => $item->product?->name ?? 'Item',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_percent' => $item->discount_percent ?? 0,
                    'tax_percent' => $item->tax_percent ?? 0,
                    'tax_id' => $item->tax_id,
                    'line_total' => $item->line_total,
                ]);
                $item->qty_invoiced = $item->quantity;
                $item->save();
            }

            $order->update(['invoice_status' => 'invoiced']);

            return $invoice->fresh(['customer', 'items', 'salesOrder']);
        });
    }

    /**
     * @param  array{customer_id:int, invoice_date?:string, due_date?:string|null, payment_term_id?:int|null, journal_id?:int|null, currency_id?:int|null, reference?:string|null, notes?:string|null, terms?:string|null, items: array<int, array>}  $data
     */
    public function createManualInvoice(array $data, User $user): Invoice
    {
        $calc = DocumentLineCalculator::summarize($data['items']);

        return DB::connection('tenant')->transaction(function () use ($data, $user, $calc) {
            $invoiceDate = $data['invoice_date'] ?? now()->toDateString();
            $term = isset($data['payment_term_id']) ? PaymentTerm::query()->find($data['payment_term_id']) : null;

            $invoice = Invoice::query()->create([
                'number' => $this->nextNumber('INV'),
                'customer_id' => $data['customer_id'],
                'invoice_date' => $invoiceDate,
                'due_date' => $data['due_date'] ?? $this->dueDate($invoiceDate, $term),
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'journal_id' => $data['journal_id'] ?? Journal::query()->where('code', 'INV')->value('id'),
                'currency_id' => $data['currency_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'status' => 'draft',
                'payment_state' => 'not_paid',
                'subtotal' => $calc['subtotal'],
                'discount_total' => $calc['discount_total'],
                'tax_total' => $calc['tax_total'],
                'grand_total' => $calc['grand_total'],
                'amount_paid' => 0,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($calc['lines'] as $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'uom_id' => $item['uom_id'] ?? null,
                    'description' => $item['description'] ?? 'Item',
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['unit_price'],
                    'discount_percent' => (int) ($item['discount_percent'] ?? 0),
                    'tax_percent' => (int) ($item['tax_percent'] ?? 0),
                    'tax_id' => $item['tax_id'] ?? null,
                    'line_total' => (int) $item['line_total'],
                ]);
            }

            return $invoice->fresh(['customer', 'items']);
        });
    }

    public function createBillFromPurchaseOrder(PurchaseOrder $order, User $user): Bill
    {
        if ($order->receipt_status === 'no' && ! $order->isReceived()) {
            throw new InvalidArgumentException('Only received (or partially received) purchase orders can be billed.');
        }

        if (Bill::query()->where('purchase_order_id', $order->id)->exists()) {
            throw new InvalidArgumentException('Purchase order already has a bill.');
        }

        $order->load('items.product', 'paymentTerm');

        return DB::connection('tenant')->transaction(function () use ($order, $user) {
            $billDate = now()->toDateString();
            $lines = [];
            $itemsPayload = [];

            foreach ($order->items as $item) {
                $qty = $item->qty_received > 0 ? $item->qty_received : ($order->isReceived() ? $item->quantity : 0);
                if ($qty <= 0) {
                    continue;
                }
                $itemsPayload[] = [
                    'quantity' => $qty,
                    'unit_price' => $item->unit_price,
                    'discount_percent' => $item->discount_percent ?? 0,
                    'tax_percent' => $item->tax_percent ?? 0,
                ];
                $lines[] = compact('item', 'qty');
            }

            if ($lines === []) {
                throw new InvalidArgumentException('No received quantities to bill.');
            }

            $calc = DocumentLineCalculator::summarize($itemsPayload);

            $bill = Bill::query()->create([
                'number' => $this->nextNumber('BILL'),
                'vendor_id' => $order->vendor_id,
                'purchase_order_id' => $order->id,
                'bill_date' => $billDate,
                'due_date' => $this->dueDate($billDate, $order->paymentTerm),
                'payment_term_id' => $order->payment_term_id,
                'journal_id' => Journal::query()->where('code', 'BILL')->value('id'),
                'currency_id' => $order->currency_id,
                'status' => 'draft',
                'payment_state' => 'not_paid',
                'subtotal' => $calc['subtotal'],
                'discount_total' => $calc['discount_total'],
                'tax_total' => $calc['tax_total'],
                'grand_total' => $calc['grand_total'],
                'amount_paid' => 0,
                'notes' => $order->notes,
                'terms' => $order->terms,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $index => $row) {
                $item = $row['item'];
                $qty = $row['qty'];
                $bill->items()->create([
                    'product_id' => $item->product_id,
                    'uom_id' => $item->uom_id,
                    'description' => $item->product?->name ?? 'Item',
                    'quantity' => $qty,
                    'unit_price' => $item->unit_price,
                    'discount_percent' => $item->discount_percent ?? 0,
                    'tax_percent' => $item->tax_percent ?? 0,
                    'tax_id' => $item->tax_id,
                    'line_total' => (int) $calc['lines'][$index]['line_total'],
                ]);
                $item->qty_invoiced = $qty;
                $item->save();
            }

            $order->load('items');
            $order->updateBillingStatus();

            return $bill->fresh(['vendor', 'items', 'purchaseOrder']);
        });
    }

    /**
     * @param  array{vendor_id:int, bill_date?:string, due_date?:string|null, payment_term_id?:int|null, journal_id?:int|null, currency_id?:int|null, reference?:string|null, notes?:string|null, terms?:string|null, items: array<int, array>}  $data
     */
    public function createManualBill(array $data, User $user): Bill
    {
        $calc = DocumentLineCalculator::summarize($data['items']);

        return DB::connection('tenant')->transaction(function () use ($data, $user, $calc) {
            $billDate = $data['bill_date'] ?? now()->toDateString();
            $term = isset($data['payment_term_id']) ? PaymentTerm::query()->find($data['payment_term_id']) : null;

            $bill = Bill::query()->create([
                'number' => $this->nextNumber('BILL'),
                'vendor_id' => $data['vendor_id'],
                'bill_date' => $billDate,
                'due_date' => $data['due_date'] ?? $this->dueDate($billDate, $term),
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'journal_id' => $data['journal_id'] ?? Journal::query()->where('code', 'BILL')->value('id'),
                'currency_id' => $data['currency_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'status' => 'draft',
                'payment_state' => 'not_paid',
                'subtotal' => $calc['subtotal'],
                'discount_total' => $calc['discount_total'],
                'tax_total' => $calc['tax_total'],
                'grand_total' => $calc['grand_total'],
                'amount_paid' => 0,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($calc['lines'] as $item) {
                $bill->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'uom_id' => $item['uom_id'] ?? null,
                    'description' => $item['description'] ?? 'Item',
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['unit_price'],
                    'discount_percent' => (int) ($item['discount_percent'] ?? 0),
                    'tax_percent' => (int) ($item['tax_percent'] ?? 0),
                    'tax_id' => $item['tax_id'] ?? null,
                    'line_total' => (int) $item['line_total'],
                ]);
            }

            return $bill->fresh(['vendor', 'items']);
        });
    }

    public function postInvoice(Invoice $invoice, User $user): Invoice
    {
        if (! $invoice->isDraft()) {
            throw new InvalidArgumentException('Only draft invoices can be posted.');
        }

        if ($invoice->items()->doesntExist()) {
            throw new InvalidArgumentException('Invoice has no items.');
        }

        return DB::connection('tenant')->transaction(function () use ($invoice, $user) {
            $invoice->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            $this->postInvoiceJournal($invoice->fresh('items'), $user);

            return $invoice->fresh();
        });
    }

    public function postBill(Bill $bill, User $user): Bill
    {
        if (! $bill->isDraft()) {
            throw new InvalidArgumentException('Only draft bills can be posted.');
        }

        if ($bill->items()->doesntExist()) {
            throw new InvalidArgumentException('Bill has no items.');
        }

        return DB::connection('tenant')->transaction(function () use ($bill, $user) {
            $bill->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            $this->postBillJournal($bill->fresh('items'), $user);

            return $bill->fresh();
        });
    }

    public function recordInvoicePayment(Invoice $invoice, int $amount, User $user, ?string $notes = null, ?int $journalId = null): Payment
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

        return DB::connection('tenant')->transaction(function () use ($invoice, $amount, $user, $notes, $journalId) {
            $payment = Payment::query()->create([
                'number' => $this->nextNumber('PAY'),
                'direction' => 'incoming',
                'invoice_id' => $invoice->id,
                'journal_id' => $journalId ?? Journal::query()->where('code', 'BANK')->value('id'),
                'currency_id' => $invoice->currency_id,
                'amount' => $amount,
                'paid_at' => now(),
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            $invoice->increment('amount_paid', $amount);
            $invoice->refresh();
            $invoice->update([
                'payment_state' => $invoice->amountDue() === 0 ? 'paid' : 'partial',
            ]);

            $this->postPaymentJournal($payment, $invoice, null, $user);

            return $payment;
        });
    }

    public function recordBillPayment(Bill $bill, int $amount, User $user, ?string $notes = null, ?int $journalId = null): Payment
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

        return DB::connection('tenant')->transaction(function () use ($bill, $amount, $user, $notes, $journalId) {
            $payment = Payment::query()->create([
                'number' => $this->nextNumber('PAY'),
                'direction' => 'outgoing',
                'bill_id' => $bill->id,
                'journal_id' => $journalId ?? Journal::query()->where('code', 'BANK')->value('id'),
                'currency_id' => $bill->currency_id,
                'amount' => $amount,
                'paid_at' => now(),
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            $bill->increment('amount_paid', $amount);
            $bill->refresh();
            $bill->update([
                'payment_state' => $bill->amountDue() === 0 ? 'paid' : 'partial',
            ]);

            $this->postPaymentJournal($payment, null, $bill, $user);

            return $payment;
        });
    }

    public function createCreditNoteFromInvoice(Invoice $invoice, User $user): Invoice
    {
        if (! $invoice->isPosted()) {
            throw new InvalidArgumentException('Only posted invoices can be reversed.');
        }

        if ($invoice->isCreditNote()) {
            throw new InvalidArgumentException('Cannot reverse a credit note.');
        }

        if ($invoice->isReversed() || $invoice->creditNotes()->exists()) {
            throw new InvalidArgumentException('Invoice already has a credit note.');
        }

        $invoice->load('items');

        return DB::connection('tenant')->transaction(function () use ($invoice, $user) {
            $credit = Invoice::query()->create([
                'number' => $this->nextNumber('CN'),
                'move_type' => 'out_refund',
                'customer_id' => $invoice->customer_id,
                'sales_order_id' => $invoice->sales_order_id,
                'reversed_invoice_id' => $invoice->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'payment_term_id' => $invoice->payment_term_id,
                'journal_id' => $invoice->journal_id,
                'currency_id' => $invoice->currency_id,
                'reference' => $invoice->number,
                'status' => 'draft',
                'payment_state' => 'not_paid',
                'subtotal' => $invoice->subtotal,
                'discount_total' => $invoice->discount_total,
                'tax_total' => $invoice->tax_total,
                'grand_total' => $invoice->grand_total,
                'amount_paid' => 0,
                'notes' => 'Credit note for '.$invoice->number,
                'terms' => $invoice->terms,
                'created_by' => $user->id,
            ]);

            foreach ($invoice->items as $item) {
                $credit->items()->create([
                    'product_id' => $item->product_id,
                    'uom_id' => $item->uom_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_percent' => $item->discount_percent,
                    'tax_percent' => $item->tax_percent,
                    'tax_id' => $item->tax_id,
                    'line_total' => $item->line_total,
                ]);
            }

            $this->postCreditNote($credit->fresh('items'), $invoice, $user);

            return $credit->fresh(['customer', 'items', 'reversedInvoice']);
        });
    }

    public function createRefundFromBill(Bill $bill, User $user): Bill
    {
        if (! $bill->isPosted()) {
            throw new InvalidArgumentException('Only posted bills can be refunded.');
        }

        if ($bill->isRefund()) {
            throw new InvalidArgumentException('Cannot refund a vendor refund.');
        }

        if ($bill->isReversed() || $bill->refunds()->exists()) {
            throw new InvalidArgumentException('Bill already has a refund.');
        }

        $bill->load('items');

        return DB::connection('tenant')->transaction(function () use ($bill, $user) {
            $refund = Bill::query()->create([
                'number' => $this->nextNumber('RFD'),
                'move_type' => 'in_refund',
                'vendor_id' => $bill->vendor_id,
                'purchase_order_id' => $bill->purchase_order_id,
                'reversed_bill_id' => $bill->id,
                'bill_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'payment_term_id' => $bill->payment_term_id,
                'journal_id' => $bill->journal_id,
                'currency_id' => $bill->currency_id,
                'reference' => $bill->number,
                'status' => 'draft',
                'payment_state' => 'not_paid',
                'subtotal' => $bill->subtotal,
                'discount_total' => $bill->discount_total,
                'tax_total' => $bill->tax_total,
                'grand_total' => $bill->grand_total,
                'amount_paid' => 0,
                'notes' => 'Refund for '.$bill->number,
                'terms' => $bill->terms,
                'created_by' => $user->id,
            ]);

            foreach ($bill->items as $item) {
                $refund->items()->create([
                    'product_id' => $item->product_id,
                    'uom_id' => $item->uom_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_percent' => $item->discount_percent,
                    'tax_percent' => $item->tax_percent,
                    'tax_id' => $item->tax_id,
                    'line_total' => $item->line_total,
                ]);
            }

            $this->postBillRefund($refund->fresh('items'), $bill, $user);

            return $refund->fresh(['vendor', 'items', 'reversedBill']);
        });
    }

    protected function postCreditNote(Invoice $credit, Invoice $original, User $user): void
    {
        $journalId = $credit->journal_id ?? Journal::query()->where('code', 'INV')->value('id');
        $ar = Account::query()->where('code', '1100')->firstOrFail();
        $income = Account::query()->where('code', '4000')->firstOrFail();
        $tax = Account::query()->where('code', '2200')->firstOrFail();
        $untaxed = $credit->grand_total - $credit->tax_total;

        $entry = JournalEntry::query()->create([
            'number' => $this->nextNumber('JE'),
            'journal_id' => $journalId,
            'date' => $credit->invoice_date ?? now()->toDateString(),
            'reference' => $credit->number,
            'status' => 'posted',
            'document_type' => Invoice::class,
            'document_id' => $credit->id,
            'narration' => 'Credit note '.$credit->number,
            'created_by' => $user->id,
        ]);

        $entry->items()->create([
            'account_id' => $income->id,
            'label' => 'Reverse income '.$credit->number,
            'partner_customer_id' => $credit->customer_id,
            'debit' => $untaxed,
            'credit' => 0,
        ]);
        if ($credit->tax_total > 0) {
            $entry->items()->create([
                'account_id' => $tax->id,
                'label' => 'Reverse tax '.$credit->number,
                'debit' => $credit->tax_total,
                'credit' => 0,
            ]);
        }
        $entry->items()->create([
            'account_id' => $ar->id,
            'label' => 'Reverse AR '.$credit->number,
            'partner_customer_id' => $credit->customer_id,
            'debit' => 0,
            'credit' => $credit->grand_total,
        ]);

        $credit->update([
            'status' => 'posted',
            'posted_at' => now(),
            'payment_state' => 'paid',
            'amount_paid' => $credit->grand_total,
        ]);

        $original->update([
            'status' => 'reversed',
            'payment_state' => 'reversed',
        ]);
    }

    protected function postBillRefund(Bill $refund, Bill $original, User $user): void
    {
        $journalId = $refund->journal_id ?? Journal::query()->where('code', 'BILL')->value('id');
        $ap = Account::query()->where('code', '2100')->firstOrFail();
        $expense = Account::query()->where('code', '5000')->firstOrFail();
        $tax = Account::query()->where('code', '1200')->firstOrFail();
        $untaxed = $refund->grand_total - $refund->tax_total;

        $entry = JournalEntry::query()->create([
            'number' => $this->nextNumber('JE'),
            'journal_id' => $journalId,
            'date' => $refund->bill_date ?? now()->toDateString(),
            'reference' => $refund->number,
            'status' => 'posted',
            'document_type' => Bill::class,
            'document_id' => $refund->id,
            'narration' => 'Vendor refund '.$refund->number,
            'created_by' => $user->id,
        ]);

        $entry->items()->create([
            'account_id' => $ap->id,
            'label' => 'Reverse AP '.$refund->number,
            'partner_vendor_id' => $refund->vendor_id,
            'debit' => $refund->grand_total,
            'credit' => 0,
        ]);
        $entry->items()->create([
            'account_id' => $expense->id,
            'label' => 'Reverse expense '.$refund->number,
            'partner_vendor_id' => $refund->vendor_id,
            'debit' => 0,
            'credit' => $untaxed,
        ]);
        if ($refund->tax_total > 0) {
            $entry->items()->create([
                'account_id' => $tax->id,
                'label' => 'Reverse tax '.$refund->number,
                'debit' => 0,
                'credit' => $refund->tax_total,
            ]);
        }

        $refund->update([
            'status' => 'posted',
            'posted_at' => now(),
            'payment_state' => 'paid',
            'amount_paid' => $refund->grand_total,
        ]);

        $original->update([
            'status' => 'reversed',
            'payment_state' => 'reversed',
        ]);
    }

    protected function postInvoiceJournal(Invoice $invoice, User $user): void
    {
        $journalId = $invoice->journal_id ?? Journal::query()->where('code', 'INV')->value('id');
        $ar = Account::query()->where('code', '1100')->firstOrFail();
        $income = Account::query()->where('code', '4000')->firstOrFail();
        $tax = Account::query()->where('code', '2200')->firstOrFail();
        $untaxed = $invoice->grand_total - $invoice->tax_total;

        $entry = JournalEntry::query()->create([
            'number' => $this->nextNumber('JE'),
            'journal_id' => $journalId,
            'date' => $invoice->invoice_date ?? now()->toDateString(),
            'reference' => $invoice->number,
            'status' => 'posted',
            'document_type' => Invoice::class,
            'document_id' => $invoice->id,
            'narration' => 'Customer invoice '.$invoice->number,
            'created_by' => $user->id,
        ]);

        $entry->items()->create([
            'account_id' => $ar->id,
            'label' => 'AR '.$invoice->number,
            'partner_customer_id' => $invoice->customer_id,
            'debit' => $invoice->grand_total,
            'credit' => 0,
        ]);
        $entry->items()->create([
            'account_id' => $income->id,
            'label' => 'Income '.$invoice->number,
            'partner_customer_id' => $invoice->customer_id,
            'debit' => 0,
            'credit' => $untaxed,
        ]);
        if ($invoice->tax_total > 0) {
            $entry->items()->create([
                'account_id' => $tax->id,
                'label' => 'Tax '.$invoice->number,
                'debit' => 0,
                'credit' => $invoice->tax_total,
            ]);
        }
    }

    protected function postBillJournal(Bill $bill, User $user): void
    {
        $journalId = $bill->journal_id ?? Journal::query()->where('code', 'BILL')->value('id');
        $ap = Account::query()->where('code', '2100')->firstOrFail();
        $expense = Account::query()->where('code', '5000')->firstOrFail();
        $tax = Account::query()->where('code', '1200')->firstOrFail();
        $untaxed = $bill->grand_total - $bill->tax_total;

        $entry = JournalEntry::query()->create([
            'number' => $this->nextNumber('JE'),
            'journal_id' => $journalId,
            'date' => $bill->bill_date ?? now()->toDateString(),
            'reference' => $bill->number,
            'status' => 'posted',
            'document_type' => Bill::class,
            'document_id' => $bill->id,
            'narration' => 'Vendor bill '.$bill->number,
            'created_by' => $user->id,
        ]);

        $entry->items()->create([
            'account_id' => $expense->id,
            'label' => 'Expense '.$bill->number,
            'partner_vendor_id' => $bill->vendor_id,
            'debit' => $untaxed,
            'credit' => 0,
        ]);
        if ($bill->tax_total > 0) {
            $entry->items()->create([
                'account_id' => $tax->id,
                'label' => 'Tax '.$bill->number,
                'debit' => $bill->tax_total,
                'credit' => 0,
            ]);
        }
        $entry->items()->create([
            'account_id' => $ap->id,
            'label' => 'AP '.$bill->number,
            'partner_vendor_id' => $bill->vendor_id,
            'debit' => 0,
            'credit' => $bill->grand_total,
        ]);
    }

    protected function postPaymentJournal(Payment $payment, ?Invoice $invoice, ?Bill $bill, User $user): void
    {
        $journalId = $payment->journal_id ?? Journal::query()->where('code', 'BANK')->value('id');
        $bank = Account::query()->where('code', '1010')->firstOrFail();
        $ar = Account::query()->where('code', '1100')->firstOrFail();
        $ap = Account::query()->where('code', '2100')->firstOrFail();

        $entry = JournalEntry::query()->create([
            'number' => $this->nextNumber('JE'),
            'journal_id' => $journalId,
            'date' => $payment->paid_at?->toDateString() ?? now()->toDateString(),
            'reference' => $payment->number,
            'status' => 'posted',
            'document_type' => Payment::class,
            'document_id' => $payment->id,
            'narration' => 'Payment '.$payment->number,
            'created_by' => $user->id,
        ]);

        if ($invoice) {
            $entry->items()->create([
                'account_id' => $bank->id,
                'label' => 'Receive '.$payment->number,
                'partner_customer_id' => $invoice->customer_id,
                'debit' => $payment->amount,
                'credit' => 0,
            ]);
            $entry->items()->create([
                'account_id' => $ar->id,
                'label' => 'Clear AR '.$payment->number,
                'partner_customer_id' => $invoice->customer_id,
                'debit' => 0,
                'credit' => $payment->amount,
            ]);
        }

        if ($bill) {
            $entry->items()->create([
                'account_id' => $ap->id,
                'label' => 'Clear AP '.$payment->number,
                'partner_vendor_id' => $bill->vendor_id,
                'debit' => $payment->amount,
                'credit' => 0,
            ]);
            $entry->items()->create([
                'account_id' => $bank->id,
                'label' => 'Pay '.$payment->number,
                'partner_vendor_id' => $bill->vendor_id,
                'debit' => 0,
                'credit' => $payment->amount,
            ]);
        }
    }

    protected function dueDate(string $date, ?PaymentTerm $term): string
    {
        $days = $term?->days ?? 0;

        return now()->parse($date)->addDays($days)->toDateString();
    }

    public function nextNumber(string $prefix): string
    {
        $fullPrefix = $prefix.'-'.now()->format('Ymd').'-';

        $latest = match ($prefix) {
            'INV', 'CN' => Invoice::query()->where('number', 'like', $fullPrefix.'%')->orderByDesc('number')->value('number'),
            'BILL', 'RFD' => Bill::query()->where('number', 'like', $fullPrefix.'%')->orderByDesc('number')->value('number'),
            'JE' => JournalEntry::query()->where('number', 'like', $fullPrefix.'%')->orderByDesc('number')->value('number'),
            default => Payment::query()->where('number', 'like', $fullPrefix.'%')->orderByDesc('number')->value('number'),
        };

        $seq = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return $fullPrefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
