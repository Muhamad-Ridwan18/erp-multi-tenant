<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WorkCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkCenterController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('manufacturing.work_centers.view'), 403);

        $workCenters = WorkCenter::query()->orderBy('name')->get();

        return view('tenant.work-centers.index', compact('workCenters'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manufacturing.work_centers.manage'), 403);

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:150'],
            'default_capacity' => ['nullable', 'integer', 'min:1'],
            'costs_per_hour' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        WorkCenter::query()->create([
            ...$data,
            'default_capacity' => $data['default_capacity'] ?? 1,
            'is_active' => true,
        ]);

        return back()->with('status', 'Work center created.');
    }
}
