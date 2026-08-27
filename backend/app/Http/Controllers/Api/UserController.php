<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user(),
            ],
        ]);
    }

    public function update(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'profile_photo' => ['nullable', 'string'],
            'fingerprint_enabled' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات کاربر ویرایش شد.',
            'data' => [
                'user' => $user->fresh(),
            ],
        ]);
    }
}
