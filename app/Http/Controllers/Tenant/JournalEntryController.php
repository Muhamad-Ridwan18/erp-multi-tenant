<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('finance.journals.view'), 403);

        $entries = JournalEntry::query()
            ->with('journal')
            ->latest('date')
            ->latest('id')
            ->limit(200)
            ->get();

        return view('tenant.journal-entries.index', compact('entries'));
    }

    public function show(Request $request, JournalEntry $journalEntry): View
    {
        abort_unless($request->user()->can('finance.journals.view'), 403);

        $journalEntry->load(['journal', 'items.account', 'creator']);

        return view('tenant.journal-entries.show', ['entry' => $journalEntry]);
    }
}
