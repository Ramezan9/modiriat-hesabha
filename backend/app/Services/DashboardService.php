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
        $receivables = [];
        $payables = [];
        $withdrawals = [];

        foreach ($currencies as $currency) {
            $deposit = Transaction::where('workspace_id', $workspaceId)
                ->where('currency', $currency)
                ->where('type', 'deposit')
                ->sum('amount');

            $withdrawal = Transaction::where('workspace_id', $workspaceId)
                ->where('currency', $currency)
                ->where('type', 'withdrawal')
                ->sum('amount');

            $receivableDeposit = Transaction::where(
                'workspace_id',
                $workspaceId
            )
                ->where('currency', $currency)
                ->where('account_type', 'receivable')
                ->where('type', 'deposit')
                ->sum('amount');

            $receivableWithdrawal = Transaction::where(
                'workspace_id',
                $workspaceId
            )
                ->where('currency', $currency)
                ->where('account_type', 'receivable')
                ->where('type', 'withdrawal')
                ->sum('amount');

            $payableDeposit = Transaction::where(
                'workspace_id',
                $workspaceId
            )
                ->where('currency', $currency)
                ->where('account_type', 'payable')
                ->where('type', 'deposit')
                ->sum('amount');

            $payableWithdrawal = Transaction::where(
                'workspace_id',
                $workspaceId
            )
                ->where('currency', $currency)
                ->where('account_type', 'payable')
                ->where('type', 'withdrawal')
                ->sum('amount');

            $balances[$currency] = [
                'deposit' => (float) $deposit,
                'withdrawal' => (float) $withdrawal,
                'balance' => (float) ($deposit - $withdrawal),
            ];

            $receivables[$currency] = [
                'deposit' => (float) $receivableDeposit,
                'withdrawal' => (float) $receivableWithdrawal,
                'balance' => (float) (
                    $receivableDeposit - $receivableWithdrawal
                ),
            ];

            $payables[$currency] = [
                'deposit' => (float) $payableDeposit,
                'withdrawal' => (float) $payableWithdrawal,
                'balance' => (float) (
                    $payableDeposit - $payableWithdrawal
                ),
            ];

            $withdrawals[$currency] = [
                'amount' => (float) $withdrawal,
            ];
        }

        return [
            'customers_count' => Customer::where(
                'workspace_id',
                $workspaceId
            )->count(),

            'balances' => $balances,

            'receivables' => $receivables,

            'payables' => $payables,

            'withdrawals' => $withdrawals,

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
