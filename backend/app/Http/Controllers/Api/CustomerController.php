<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::where(
            'workspace_id',
            $request->user()->workspace_id
        )
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'profile_photo' => ['nullable', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $customer = Customer::create($data);

        return response()->json([
            'success' => true,
            'message' => 'مشتری با موفقیت اضافه شد.',
            'data' => $customer,
        ], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load('transactions');

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function update(
        Request $request,
        Customer $customer
    ): JsonResponse {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'profile_photo' => ['nullable', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $customer->update($data);

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات مشتری ویرایش شد.',
            'data' => $customer->fresh(),
        ]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'مشتری حذف شد.',
        ]);
    }
}
