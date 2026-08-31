<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    private function ensureMember(
        Request $request,
        int $workspaceId
    ): WorkspaceMember {
        return WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    private function ensureManager(
        Request $request,
        int $workspaceId
    ): WorkspaceMember {
        $member = $this->ensureMember($request, $workspaceId);

        abort_unless(
            $member->role === 'manager',
            403,
            'فقط مدیر فضای کاری اجازه انجام این عملیات را دارد.'
        );

        return $member;
    }

    public function index(Request $request): JsonResponse
    {
        $workspaceId = $request->query('workspace_id');

        if (!$workspaceId) {
            return response()->json([
                'success' => false,
                'message' => 'workspace_id الزامی است.',
            ], 422);
        }

        $this->ensureMember($request, (int) $workspaceId);

        $transactions = Transaction::where(
            'workspace_id',
            $workspaceId
        )
            ->with('customer')
            ->latest('transaction_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'workspace_id' => [
                'required',
                'integer',
                'exists:workspaces,id',
            ],
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],
            'type' => [
                'required',
                'in:deposit,withdrawal',
            ],
            'currency' => [
                'required',
                'in:AFN,TOMAN,USD,TRY',
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'amount_in_words' => [
                'nullable',
                'string',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'transaction_date' => [
                'required',
                'date',
            ],
        ]);

        $this->ensureManager(
            $request,
            (int) $data['workspace_id']
        );

        $customer = Customer::where(
            'id',
            $data['customer_id']
        )
            ->where(
                'workspace_id',
                $data['workspace_id']
            )
            ->firstOrFail();

        $transaction = Transaction::create([
            'workspace_id' => $data['workspace_id'],
            'customer_id' => $customer->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'amount_in_words' => $data['amount_in_words'] ?? null,
            'description' => $data['description'] ?? null,
            'transaction_date' => $data['transaction_date'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تراکنش با موفقیت ثبت شد.',
            'data' => $transaction->load('customer'),
        ], 201);
    }

    public function show(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $this->ensureMember(
            $request,
            $transaction->workspace_id
        );

        return response()->json([
            'success' => true,
            'data' => $transaction->load(
                'customer',
                'receipts'
            ),
        ]);
    }

    public function update(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $this->ensureManager(
            $request,
            $transaction->workspace_id
        );

        $data = $request->validate([
            'type' => [
                'sometimes',
                'in:deposit,withdrawal',
            ],
            'currency' => [
                'sometimes',
                'in:AFN,TOMAN,USD,TRY',
            ],
            'amount' => [
                'sometimes',
                'numeric',
                'gt:0',
            ],
            'amount_in_words' => [
                'nullable',
                'string',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'transaction_date' => [
                'sometimes',
                'date',
            ],
        ]);

        $transaction->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تراکنش ویرایش شد.',
            'data' => $transaction->fresh()->load('customer'),
        ]);
    }

    public function destroy(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $this->ensureManager(
            $request,
            $transaction->workspace_id
        );

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'تراکنش حذف شد.',
        ]);
    }
}
