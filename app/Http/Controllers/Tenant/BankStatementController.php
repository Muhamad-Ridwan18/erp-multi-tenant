<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Journal;
use App\Models\Payment;
use App\Services\BankReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class BankStatementController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('finance.bank_statements.view'), 403);

        $statements = BankStatement::query()->with('journal')->withCount('lines')->latest()->get();

        return view('tenant.bank-statements.index', compact('statements'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('finance.bank_statements.manage'), 403);

        $journals = Journal::query()->whereIn('type', ['bank', 'cash'])->where('is_active', true)->orderBy('code')->get();

        return view('tenant.bank-statements.create', compact('journals'));
    }

    public function store(Request $request, BankReconciliationService $reconciliation): RedirectResponse
    {
        abort_unless($request->user()->can('finance.bank_statements.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:100'],
            'journal_id' => ['nullable', Rule::exists(Journal::class, 'id')],
            'date' => ['nullable', 'date'],
            'balance_start' => ['nullable', 'integer'],
            'balance_end' => ['nullable', 'integer'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.date' => ['nullable', 'date'],
            'lines.*.payment_reference' => ['nullable', 'string', 'max:100'],
            'lines.*.partner_name' => ['nullable', 'string', 'max:150'],
            'lines.*.label' => ['nullable', 'string', 'max:255'],
            'lines.*.amount' => ['required', 'integer', 'not_in:0'],
        ]);

        try {
            $statement = $reconciliation->create($data, $request->user());
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.bank-statements.show', $statement)
            ->with('status', 'Bank statement created.');
    }

    public function show(Request $request, BankStatement $bankStatement, BankReconciliationService $reconciliation): View
    {
        abort_unless($request->user()->can('finance.bank_statements.view'), 403);

        $bankStatement->load(['journal', 'lines.payment', 'creator']);
        $suggestions = [];
        foreach ($bankStatement->lines->where('is_reconciled', false) as $line) {
            $suggestions[$line->id] = $reconciliation->suggestPayments($line);
        }

        return view('tenant.bank-statements.show', [
            'statement' => $bankStatement,
            'suggestions' => $suggestions,
        ]);
    }

    public function match(Request $request, BankStatement $bankStatement, BankStatementLine $line, BankReconciliationService $reconciliation): RedirectResponse
    {
        abort_unless($request->user()->can('finance.bank_statements.reconcile'), 403);
        abort_unless((int) $line->bank_statement_id === (int) $bankStatement->id, 404);

        $data = $request->validate([
            'payment_id' => ['required', Rule::exists(Payment::class, 'id')],
        ]);

        try {
            $reconciliation->match($line, Payment::query()->findOrFail($data['payment_id']));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['payment_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Line matched to payment.');
    }

    public function unmatch(Request $request, BankStatement $bankStatement, BankStatementLine $line, BankReconciliationService $reconciliation): RedirectResponse
    {
        abort_unless($request->user()->can('finance.bank_statements.reconcile'), 403);
        abort_unless((int) $line->bank_statement_id === (int) $bankStatement->id, 404);

        try {
            $reconciliation->unmatch($line);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['line' => $e->getMessage()]);
        }

        return back()->with('status', 'Line unmatched.');
    }

    public function complete(Request $request, BankStatement $bankStatement, BankReconciliationService $reconciliation): RedirectResponse
    {
        abort_unless($request->user()->can('finance.bank_statements.reconcile'), 403);

        try {
            $reconciliation->complete($bankStatement);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['statement' => $e->getMessage()]);
        }

        return back()->with('status', 'Bank statement completed.');
    }
}
