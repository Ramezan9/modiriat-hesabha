<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'alpha_num',
                'min:3',
                'max:50',
                'unique:users,username',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'password' => [
                'required',
                'digits:6',
            ],
            'pin' => [
                'nullable',
                'digits:4',
            ],
        ]);

        $result = $this->authService->register($data);

        return response()->json([
            'success' => true,
            'message' => 'ثبت‌نام با موفقیت انجام شد.',
            'data' => $result,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'digits:6'],
        ]);

        try {
            $result = $this->authService->login(
                $data['username'],
                $data['password']
            );

            return response()->json([
                'success' => true,
                'message' => 'ورود با موفقیت انجام شد.',
                'data' => $result,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'خروج با موفقیت انجام شد.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return response()->json([
            'success' => true,
            'message' => 'از همه دستگاه‌ها خارج شدید.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user(),
            ],
        ]);
    }
}
