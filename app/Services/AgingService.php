<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AgingService
{
    /**
     * @return array{as_of:string, buckets: array<string, int>, rows: Collection<int, array{document:string, partner:string, date:string, due:string, total:int, residual:int, bucket:string, days_overdue:int}>}
     */
    public function receivables(?Carbon $asOf = null): array
    {
        $asOf ??= now();

        $invoices = Invoice::query()
            ->with('customer')
            ->where('status', 'posted')
            ->where('move_type', 'out_invoice')
            ->whereColumn('amount_paid', '<', 'grand_total')
            ->orderBy('due_date')
            ->get();

        return $this->build($invoices, $asOf, fn (Invoice $invoice) => [
            'document' => $invoice->number,
            'partner' => $invoice->customer?->name ?? '—',
            'date' => $invoice->invoice_date?->toDateString() ?? '',
            'due' => $invoice->due_date?->toDateString() ?? $invoice->invoice_date?->toDateString() ?? '',
            'total' => (int) ($invoice->grand_total ?: $invoice->subtotal),
            'residual' => $invoice->amountDue(),
        ]);
    }

    /**
     * @return array{as_of:string, buckets: array<string, int>, rows: Collection<int, array{document:string, partner:string, date:string, due:string, total:int, residual:int, bucket:string, days_overdue:int}>}
     */
    public function payables(?Carbon $asOf = null): array
    {
        $asOf ??= now();

        $bills = Bill::query()
            ->with('vendor')
            ->where('status', 'posted')
            ->where('move_type', 'in_invoice')
            ->whereColumn('amount_paid', '<', 'grand_total')
            ->orderBy('due_date')
            ->get();

        return $this->build($bills, $asOf, fn (Bill $bill) => [
            'document' => $bill->number,
            'partner' => $bill->vendor?->name ?? '—',
            'date' => $bill->bill_date?->toDateString() ?? '',
            'due' => $bill->due_date?->toDateString() ?? $bill->bill_date?->toDateString() ?? '',
            'total' => (int) ($bill->grand_total ?: $bill->subtotal),
            'residual' => $bill->amountDue(),
        ]);
    }

    /**
     * @param  Collection<int, Invoice|Bill>  $documents
     * @param  callable(Invoice|Bill): array{document:string, partner:string, date:string, due:string, total:int, residual:int}  $mapper
     * @return array{as_of:string, buckets: array<string, int>, rows: Collection<int, array{document:string, partner:string, date:string, due:string, total:int, residual:int, bucket:string, days_overdue:int}>}
     */
    protected function build(Collection $documents, Carbon $asOf, callable $mapper): array
    {
        $buckets = [
            'current' => 0,
            '1_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '90_plus' => 0,
        ];

        $rows = $documents->map(function ($document) use ($asOf, $mapper, &$buckets) {
            $row = $mapper($document);
            $due = Carbon::parse($row['due'] ?: $asOf->toDateString())->startOfDay();
            $days = $due->diffInDays($asOf->copy()->startOfDay(), false);
            $bucket = $this->bucketForDays($days);
            $buckets[$bucket] += $row['residual'];

            return [
                ...$row,
                'bucket' => $bucket,
                'days_overdue' => max(0, $days),
            ];
        });

        return [
            'as_of' => $asOf->toDateString(),
            'buckets' => $buckets,
            'rows' => $rows,
        ];
    }

    protected function bucketForDays(int $days): string
    {
        if ($days <= 0) {
            return 'current';
        }
        if ($days <= 30) {
            return '1_30';
        }
        if ($days <= 60) {
            return '31_60';
        }
        if ($days <= 90) {
            return '61_90';
        }

        return '90_plus';
    }
}
