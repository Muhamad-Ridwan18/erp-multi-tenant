<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('finance.invoices.view'), 403);

        $invoices = Invoice::query()
            ->with('customer')
            ->latest()
            ->get();

        return view('tenant.invoices.index', compact('invoices'));
    }

    public function show(Request $request, Invoice $invoice): View
    {
        abort_unless($request->user()->can('finance.invoices.view'), 403);

        $invoice->load(['customer', 'items.product', 'salesOrder', 'payments', 'creator']);

        return view('tenant.invoices.show', compact('invoice'));
    }

    public function storeFromOrder(Request $request, SalesOrder $order, FinanceService $finance): RedirectResponse
    {
        abort_unless($request->user()->can('finance.invoices.create'), 403);

        try {
            $invoice = $finance->createInvoiceFromSalesOrder($order, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.invoices.show', $invoice)
            ->with('status', 'Invoice created as draft.');
    }

    public function post(Request $request, Invoice $invoice, FinanceService $finance): RedirectResponse
    {
        abort_unless($request->user()->can('finance.invoices.post'), 403);

        try {
            $finance->postInvoice($invoice);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }

        return back()->with('status', 'Invoice posted.');
    }

    public function pay(Request $request, Invoice $invoice, FinanceService $finance): RedirectResponse
    {
        abort_unless($request->user()->can('finance.payments.create'), 403);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $finance->recordInvoicePayment($invoice, (int) $data['amount'], $request->user(), $data['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', 'Payment recorded.');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($request->user()->can('finance.invoices.delete'), 403);
        abort_unless($invoice->isDraft(), 403, 'Only draft invoices can be deleted.');

        $invoice->items()->delete();
        $invoice->delete();

        return redirect()
            ->route('tenant.invoices.index')
            ->with('status', 'Draft invoice deleted.');
    }
}
