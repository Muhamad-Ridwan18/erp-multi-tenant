<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Journal;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\FinanceService;
use App\Support\DocumentLineDescriber;
use App\Support\TenantMasterData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

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

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('finance.bills.create'), 403);

        $vendors = Vendor::query()->orderBy('name')->get();
        $products = Product::query()->where('is_active', true)->orderBy('name')->get();
        $taxes = TenantMasterData::taxes();
        $paymentTerms = TenantMasterData::paymentTerms();
        $journals = TenantMasterData::journals();

        return view('tenant.bills.create', compact('vendors', 'products', 'taxes', 'paymentTerms', 'journals'));
    }

    public function store(Request $request, FinanceService $finance): RedirectResponse
    {
        abort_unless($request->user()->can('finance.bills.create'), 403);

        $data = $request->validate([
            'vendor_id' => ['required', Rule::exists(Vendor::class, 'id')],
            'bill_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'payment_term_id' => ['nullable', Rule::exists(PaymentTerm::class, 'id')],
            'journal_id' => ['nullable', Rule::exists(Journal::class, 'id')],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists(Product::class, 'id')],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $data['items'] = DocumentLineDescriber::describe($data['items']);

        try {
            $bill = $finance->createManualBill($data, $request->user());
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.bills.show', $bill)
            ->with('status', 'Bill created as draft.');
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
            $finance->postBill($bill, $request->user());
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
