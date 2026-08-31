<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    /**
     * بررسی عضویت کاربر در Workspace
     */
    private function ensureMember(
        Request $request,
        int $workspaceId
    ): WorkspaceMember {
        return WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    /**
     * بررسی اینکه کاربر مدیر Workspace است
     */
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

    public function index(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $this->ensureMember(
            $request,
            $transaction->workspace_id
        );

        $receipts = $transaction->receipts()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $receipts,
        ]);
    }

    public function store(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $this->ensureManager(
            $request,
            $transaction->workspace_id
        );

        $data = $request->validate([
            'file_path' => [
                'required',
                'string',
                'max:500',
            ],
            'file_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'mime_type' => [
                'nullable',
                'string',
                'max:100',
            ],
            'file_size' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $receipt = $transaction->receipts()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'فیش با موفقیت ثبت شد.',
            'data' => $receipt,
        ], 201);
    }

    public function show(
        Request $request,
        Receipt $receipt
    ): JsonResponse {
        $receipt->load('transaction');

        $this->ensureMember(
            $request,
            $receipt->transaction->workspace_id
        );

        return response()->json([
            'success' => true,
            'data' => $receipt,
        ]);
    }

    public function destroy(
        Request $request,
        Receipt $receipt
    ): JsonResponse {
        $receipt->load('transaction');

        $this->ensureManager(
            $request,
            $receipt->transaction->workspace_id
        );

        $receipt->delete();

        return response()->json([
            'success' => true,
            'message' => 'فیش حذف شد.',
        ]);
    }
}
