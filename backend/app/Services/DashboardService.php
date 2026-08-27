<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Customer;

class DashboardService
{
    public function getSummary(int $workspaceId): array
    {
        $transactions = Transaction::where(
            'workspace_id',
            $workspaceId
        )->get();

        $currencies = [
            'AFN',
            'TOMAN',
            'USD',
            'TRY',
        ];

        $balances = [];

        foreach ($currencies as $currency) {
            $deposits = $transactions
                ->where('currency', $currency)
                ->where('type', 'deposit')
                ->sum('amount');

            $withdrawals = $transactions
                ->where('currency', $currency)
                ->where('type', 'withdrawal')
                ->sum('amount');

            $balances[$currency] = [
                'deposit' => $deposits,
                'withdrawal' => $withdrawals,
                'balance' => $deposits - $withdrawals,
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
