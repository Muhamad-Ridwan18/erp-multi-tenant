<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Models\WorkCenter;
use App\Support\TenantMasterData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BomController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('manufacturing.boms.view'), 403);

        $boms = BillOfMaterial::query()
            ->with(['product', 'workCenter', 'lines'])
            ->latest()
            ->get();

        return view('tenant.boms.index', compact('boms'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('manufacturing.boms.create'), 403);

        $products = Product::query()->where('is_active', true)->where('type', 'goods')->orderBy('name')->get();
        $workCenters = WorkCenter::query()->where('is_active', true)->orderBy('name')->get();
        $uoms = TenantMasterData::uoms();

        return view('tenant.boms.create', compact('products', 'workCenters', 'uoms'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manufacturing.boms.create'), 403);

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40'],
            'product_id' => ['required', Rule::exists(Product::class, 'id')],
            'uom_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
            'work_center_id' => ['nullable', Rule::exists(WorkCenter::class, 'id')],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', Rule::exists(Product::class, 'id')],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.uom_id' => ['nullable', 'integer'],
            'operations' => ['nullable', 'array'],
            'operations.*.name' => ['required_with:operations', 'string', 'max:150'],
            'operations.*.work_center_id' => ['nullable', Rule::exists(WorkCenter::class, 'id')],
            'operations.*.duration_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        foreach ($data['lines'] as $line) {
            if ((int) $line['product_id'] === (int) $data['product_id']) {
                return back()->withInput()->withErrors(['lines' => 'Finished product cannot be a component of its own BOM.']);
            }
        }

        $bom = DB::connection('tenant')->transaction(function () use ($data, $request) {
            $bom = BillOfMaterial::query()->create([
                'code' => $data['code'] ?? null,
                'product_id' => $data['product_id'],
                'uom_id' => $data['uom_id'] ?? null,
                'quantity' => $data['quantity'],
                'work_center_id' => $data['work_center_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
                'created_by' => $request->user()->id,
            ]);

            foreach (array_values($data['lines']) as $index => $line) {
                $bom->lines()->create([
                    'product_id' => $line['product_id'],
                    'uom_id' => $line['uom_id'] ?? null,
                    'quantity' => $line['quantity'],
                    'sort' => $index,
                ]);
            }

            foreach (array_values($data['operations'] ?? []) as $index => $operation) {
                if (blank($operation['name'] ?? null)) {
                    continue;
                }
                $bom->operations()->create([
                    'name' => $operation['name'],
                    'work_center_id' => $operation['work_center_id'] ?? $data['work_center_id'] ?? null,
                    'duration_minutes' => (int) ($operation['duration_minutes'] ?? 0),
                    'sort' => $index,
                ]);
            }

            return $bom;
        });

        return redirect()
            ->route('tenant.boms.show', $bom)
            ->with('status', 'Bill of materials created.');
    }

    public function show(Request $request, BillOfMaterial $bom): View
    {
        abort_unless($request->user()->can('manufacturing.boms.view'), 403);

        $bom->load(['product', 'workCenter', 'lines.product', 'operations.workCenter', 'creator']);

        return view('tenant.boms.show', compact('bom'));
    }
}
