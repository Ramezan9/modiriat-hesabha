<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;

class DashboardService
{
    public function getSummary(int $workspaceId): array
    {
        $currencies = [
            'AFN',
            'TOMAN',
            'USD',
            'TRY',
        ];

        $balances = [];

        foreach ($currencies as $currency) {
            $deposit = Transaction::where('workspace_id', $workspaceId)
                ->where('currency', $currency)
                ->where('type', 'deposit')
                ->sum('amount');

            $withdrawal = Transaction::where('workspace_id', $workspaceId)
                ->where('currency', $currency)
                ->where('type', 'withdrawal')
                ->sum('amount');

            $balances[$currency] = [
                'deposit' => (float) $deposit,
                'withdrawal' => (float) $withdrawal,
                'balance' => (float) ($deposit - $withdrawal),
            ];
        }

        return [
            'customers_count' => Customer::where(
                'workspace_id',
                $workspaceId
            )->count(),

            'balances' => $balances,

            'recent_transactions' => Transaction::where(
                'workspace_id',
                $workspaceId
            )
                ->with('customer')
                ->latest('transaction_date')
                ->limit(10)
                ->get(),
        ];
    }
}
