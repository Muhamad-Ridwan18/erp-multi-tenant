<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\PurchaseOrder;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class BillController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('finance.bills.view'), 403);

        $bills = Bill::query()
            ->with('vendor')
            ->latest()
            ->get();

        return view('tenant.bills.index', compact('bills'));
    }

    public function show(Request $request, Bill $bill): View
    {
        abort_unless($request->user()->can('finance.bills.view'), 403);

        $bill->load(['vendor', 'items.product', 'purchaseOrder', 'payments', 'creator']);

        return view('tenant.bills.show', compact('bill'));
    }

    public function storeFromPurchase(Request $request, PurchaseOrder $purchase, FinanceService $finance): RedirectResponse
    {
        abort_unless($request->user()->can('finance.bills.create'), 403);

        try {
            $bill = $finance->createBillFromPurchaseOrder($purchase, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.bills.show', $bill)
            ->with('status', 'Bill created as draft.');
    }

    public function post(Request $request, Bill $bill, FinanceService $finance): RedirectResponse
    {
        abort_unless($request->user()->can('finance.bills.post'), 403);

        try {
            $finance->postBill($bill);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['bill' => $e->getMessage()]);
        }

        return back()->with('status', 'Bill posted.');
    }

    public function pay(Request $request, Bill $bill, FinanceService $finance): RedirectResponse
    {
        abort_unless($request->user()->can('finance.payments.create'), 403);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $finance->recordBillPayment($bill, (int) $data['amount'], $request->user(), $data['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', 'Payment recorded.');
    }

    public function destroy(Request $request, Bill $bill): RedirectResponse
    {
        abort_unless($request->user()->can('finance.bills.delete'), 403);
        abort_unless($bill->isDraft(), 403, 'Only draft bills can be deleted.');

        $bill->items()->delete();
        $bill->delete();

        return redirect()
            ->route('tenant.bills.index')
            ->with('status', 'Draft bill deleted.');
    }
}
