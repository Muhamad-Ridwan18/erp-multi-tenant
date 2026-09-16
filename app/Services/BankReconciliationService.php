<?php

namespace App\Services;

use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BankReconciliationService
{
    /**
     * @param  array{name:string, reference?:string|null, journal_id?:int|null, date?:string|null, balance_start?:int, balance_end?:int, lines: array<int, array{date?:string|null, payment_reference?:string|null, partner_name?:string|null, label?:string|null, amount:int}>}  $data
     */
    public function create(array $data, User $user): BankStatement
    {
        return DB::connection('tenant')->transaction(function () use ($data, $user) {
            $statement = BankStatement::query()->create([
                'name' => $data['name'],
                'reference' => $data['reference'] ?? null,
                'journal_id' => $data['journal_id'] ?? Journal::query()->where('code', 'BANK')->value('id'),
                'date' => $data['date'] ?? now()->toDateString(),
                'balance_start' => (int) ($data['balance_start'] ?? 0),
                'balance_end' => (int) ($data['balance_end'] ?? 0),
                'status' => 'open',
                'created_by' => $user->id,
            ]);

            foreach (array_values($data['lines'] ?? []) as $index => $line) {
                $amount = (int) ($line['amount'] ?? 0);
                if ($amount === 0) {
                    continue;
                }
                $statement->lines()->create([
                    'date' => $line['date'] ?? $statement->date,
                    'payment_reference' => $line['payment_reference'] ?? null,
                    'partner_name' => $line['partner_name'] ?? null,
                    'label' => $line['label'] ?? null,
                    'amount' => $amount,
                    'is_reconciled' => false,
                    'sort' => $index,
                ]);
            }

            return $statement->fresh('lines');
        });
    }

    public function match(BankStatementLine $line, Payment $payment): BankStatementLine
    {
        if ($line->is_reconciled) {
            throw new InvalidArgumentException('Statement line already reconciled.');
        }

        if ($payment->is_reconciled) {
            throw new InvalidArgumentException('Payment already reconciled.');
        }

        $paymentAmount = (int) ($payment->amount_company ?: $payment->amount);
        $lineAbs = abs((int) $line->amount);

        if ($lineAbs !== $paymentAmount) {
            throw new InvalidArgumentException('Payment amount does not match statement line.');
        }

        if ($payment->direction === 'incoming' && $line->amount < 0) {
            throw new InvalidArgumentException('Incoming payment cannot match a debit (outflow) line.');
        }

        if ($payment->direction === 'outgoing' && $line->amount > 0) {
            throw new InvalidArgumentException('Outgoing payment cannot match a credit (inflow) line.');
        }

        return DB::connection('tenant')->transaction(function () use ($line, $payment) {
            $line->update([
                'is_reconciled' => true,
                'payment_id' => $payment->id,
            ]);

            $payment->update([
                'is_reconciled' => true,
                'bank_statement_line_id' => $line->id,
            ]);

            return $line->fresh('payment');
        });
    }

    public function unmatch(BankStatementLine $line): BankStatementLine
    {
        if (! $line->is_reconciled) {
            throw new InvalidArgumentException('Line is not reconciled.');
        }

        return DB::connection('tenant')->transaction(function () use ($line) {
            $payment = $line->payment;
            $line->update([
                'is_reconciled' => false,
                'payment_id' => null,
            ]);

            if ($payment) {
                $payment->update([
                    'is_reconciled' => false,
                    'bank_statement_line_id' => null,
                ]);
            }

            return $line->fresh();
        });
    }

    public function complete(BankStatement $statement): BankStatement
    {
        if (! $statement->isOpen()) {
            throw new InvalidArgumentException('Statement already completed.');
        }

        if ($statement->lines()->where('is_reconciled', false)->exists()) {
            throw new InvalidArgumentException('All lines must be reconciled before completing.');
        }

        $statement->update(['status' => 'done']);

        return $statement->fresh();
    }

    /**
     * Suggest unmatched payments for a line by absolute amount.
     *
     * @return Collection<int, Payment>
     */
    public function suggestPayments(BankStatementLine $line)
    {
        $abs = abs((int) $line->amount);
        $direction = $line->amount >= 0 ? 'incoming' : 'outgoing';

        return Payment::query()
            ->where('is_reconciled', false)
            ->where('direction', $direction)
            ->where(function ($q) use ($abs) {
                $q->where('amount_company', $abs)
                    ->orWhere(function ($q2) use ($abs) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('amount_company')->orWhere('amount_company', 0);
                        })->where('amount', $abs);
                    });
            })
            ->latest('paid_at')
            ->limit(20)
            ->get();
    }
}
