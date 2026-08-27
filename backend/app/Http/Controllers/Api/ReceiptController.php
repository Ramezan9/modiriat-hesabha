<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $receipts = $transaction->receipts()->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $receipts,
        ]);
    }

    public function store(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $data = $request->validate([
            'file_path' => ['required', 'string', 'max:500'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'file_size' => ['nullable', 'integer', 'min:0'],
        ]);

        $receipt = $transaction->receipts()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'فیش با موفقیت ثبت شد.',
            'data' => $receipt,
        ], 201);
    }

    public function show(Receipt $receipt): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $receipt,
        ]);
    }

    public function destroy(Receipt $receipt): JsonResponse
    {
        $receipt->delete();

        return response()->json([
            'success' => true,
            'message' => 'فیش حذف شد.',
        ]);
    }
}
