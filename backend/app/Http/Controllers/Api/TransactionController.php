<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transactions = Transaction::where(
            'workspace_id',
            $request->user()->workspace_id
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
            'workspace_id' => ['required', 'integer'],
            'customer_id' => ['required', 'integer'],
            'type' => ['required', 'in:deposit,withdrawal'],
            'currency' => ['required', 'in:AFN,TOMAN,USD,TRY'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'amount_in_words' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['required', 'date'],
        ]);

        $data['user_id'] = $request->user()->id;

        $transaction = Transaction::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تراکنش با موفقیت ثبت شد.',
            'data' => $transaction->load('customer'),
        ], 201);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $transaction->load('customer', 'receipts'),
        ]);
    }

    public function update(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $data = $request->validate([
            'type' => ['sometimes', 'in:deposit,withdrawal'],
            'currency' => ['sometimes', 'in:AFN,TOMAN,USD,TRY'],
            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'amount_in_words' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['sometimes', 'date'],
        ]);

        $transaction->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تراکنش ویرایش شد.',
            'data' => $transaction->fresh(),
        ]);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'تراکنش حذف شد.',
        ]);
    }
}
