<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'department' => 'nullable|string',
        ]);

        $result = $this->authService->register($data);

        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $result['user'],
            'token'   => $result['token'],
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $result = $this->authService->login(
            $data['email'],
            $data['password']
        );

        if ($result['error'] ?? false) {
            return response()->json([
                'message' => $result['error'],
            ], 403);
        }

        return response()->json($result);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|string|min:8',
        ]);

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

    public function sendOtp(Request $request)
    {
        $email = $request->query('email');
        $otp = $this->authService->sendOtp($email);

        if ($otp['error'] ?? false) {
            return response()->json([
                'message' => $otp['error'],
            ], 400);
        }
        return response()->json([
            'message' => 'OTP generated successfully',
            'otp'     => $otp['otp'],
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

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
}
