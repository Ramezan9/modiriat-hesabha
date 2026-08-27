<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'مدیریت حساب‌ها API فعال است.',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);

    Route::get('/user', [UserController::class, 'me']);
    Route::put('/user', [UserController::class, 'update']);

    Route::apiResource('workspaces', WorkspaceController::class);

    Route::get(
        '/workspaces/{workspaceId}/members',
        [WorkspaceMemberController::class, 'index']
    );

    Route::post(
        '/workspaces/{workspaceId}/members',
        [WorkspaceMemberController::class, 'store']
    );

    Route::put(
        '/workspace-members/{member}',
        [WorkspaceMemberController::class, 'update']
    );

    Route::delete(
        '/workspace-members/{member}',
        [WorkspaceMemberController::class, 'destroy']
    );

    Route::apiResource('customers', CustomerController::class);

    Route::apiResource('transactions', TransactionController::class);

    Route::get(
        '/transactions/{transaction}/receipts',
        [ReceiptController::class, 'index']
    );

    Route::post(
        '/transactions/{transaction}/receipts',
        [ReceiptController::class, 'store']
    );

    Route::get(
        '/receipts/{receipt}',
        [ReceiptController::class, 'show']
    );

    Route::delete(
        '/receipts/{receipt}',
        [ReceiptController::class, 'destroy']
    );

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    );
});
