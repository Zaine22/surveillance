<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\GetUsersRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\StoreUserRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Requests\Auth\VerifyUserRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterUserRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->register($data);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $result['user'],
            'token' => $result['token'],
        ], 201);
    }

    public function login(LoginUserRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->login(
            $data['email'],
            $data['password'],
            $data['otp']
        );

        if ($result['error'] ?? false) {
            return response()->json([
                'message' => $result['error'],
            ], 403);
        }

        $result = $this->authService->login(
            $data['email'],
            $data['password'],
            $data['otp']
        );

        if ($result['error'] ?? false) {
            return response()->json([
                'message' => $result['error'],
            ], 403);
        }

        return response()->json($result);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();

        $this->authService->changePassword(
            $request->user(),
            $data['current_password'],
            $data['new_password']
        );

        return response()->json([
            'message' => 'Password changed successfully. Please login again.',
        ]);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function sendOtp(SendOtpRequest $request)
    {
        // 1️⃣ Validate input
        $validate = $request->validated();

        // 2️⃣ Call service
        $result = $this->authService->sendOtp($validate['email']);

        // 3️⃣ Handle error response
        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'][0] ?? 'Failed to send OTP. Please try again later.',
            ], 400);
        }

        // 4️⃣ Prepare response
        $response = [
            'message' => $result['message'] ?? 'OTP sent successfully.',
        ];

        // ✅ Optional: include OTP only in local/dev environment
        if (app()->environment('local') && isset($result['otp'])) {
            $response['otp'] = $result['otp'];
        }

        return response()->json($response, 200);
    }

    public function verifyOtp(VerifyUserRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->verifyOtp(
            $data['email'],
            $data['otp']
        );

        if ($result['error'] ?? false) {
            return response()->json([
                'message' => $result['error'],
            ], 400);
        }

        return response()->json([
            'message' => 'OTP verified successfully',
        ]);
    }

    public function checkPasswordExpiry(Request $request)
    {
        $isExpired = $this->authService->checkPasswordExpiry($request->user());

        return response()->json([
            'password_expired' => $isExpired,
        ]);
    }

    public function updateUser(UpdateUserRequest $request)
    {
        $data = $request->validated();

        $updatedUser = $this->authService->updateUser($request->user(), $data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $updatedUser,
        ]);
    }

    public function index(GetUsersRequest $request)
    {
        $users = $this->authService->getAllUsers($request);

        return response()->json($users);
    }

    public function createByAdmin(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        Log::info('user'.$user);

        $data['created_by'] = $user->id;

        $user = $this->authService->createUserByAdmin($data);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }
}