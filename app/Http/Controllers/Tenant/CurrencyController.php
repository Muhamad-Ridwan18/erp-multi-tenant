<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('finance.currencies.view'), 403);

        $currencies = Currency::query()->orderBy('code')->get();

        return view('tenant.currencies.index', compact('currencies'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('finance.currencies.manage'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique(Currency::class, 'code')],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'rate' => ['required', 'numeric', 'min:0.000001'],
        ]);

        Currency::query()->create([
            ...$data,
            'symbol' => $data['symbol'] ?? $data['code'],
            'is_active' => true,
        ]);

        return back()->with('status', 'Currency created.');
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        abort_unless($request->user()->can('finance.currencies.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'rate' => ['required', 'numeric', 'min:0.000001'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($currency->code === 'IDR') {
            $data['rate'] = 1;
        }

        $currency->update([
            ...$data,
            'is_active' => $request->boolean('is_active', $currency->is_active),
        ]);

        return back()->with('status', "Updated {$currency->code} rate.");
    }
}
