<?php
namespace App\Http\Controllers;

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
            // 'message' => 'User registered successfully',
            'message' => '用戶註冊成功',
            'user'    => $result['user'],
            'token'   => $result['token'],
        ], 201);
    }

    // public function login(LoginUserRequest $request)
    // {
    //     $data = $request->validated();

    //     $result = $this->authService->login(
    //         $data['email'],
    //         $data['password'],
    //         $data['otp']
    //     );

    //     if ($result['error'] ?? false) {
    //         return response()->json([
    //             'message' => $result['error'],
    //         ], 403);
    //     }

    //     $result = $this->authService->login(
    //         $data['email'],
    //         $data['password'],
    //         $data['otp']
    //     );

    //     if ($result['error'] ?? false) {
    //         return response()->json([
    //             'message' => $result['error'],
    //         ], 403);
    //     }

    //     return response()->json($result);
    // }
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

        return response()->json($result);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->changePassword(
            $request->user(),
            $data['current_password'],
            $data['new_password']
        );

        if ($result['error'] ?? false) {
            return response()->json([
                'message' => $result['error'],
            ], 403);
        }

        return response()->json([
            // 'message' => 'Password changed successfully. Please login again.',
            'message' => '密碼變更成功，請重新登入。',
        ]);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => '登出成功',
        ]);
    }

    public function sendOtp(SendOtpRequest $request)
    {

        $validate = $request->validated();


        $result = $this->authService->sendOtp($validate['email']);


        if (isset($result['error'])) {
            return response()->json([
                // 'message' => $result['error'] ?? 'Failed to send OTP. Please try again later.',
                'message' => $result['error'] ?? '發送驗證碼失敗，請稍後再試。'
            ], 400);
        }


        $response = [
            // 'message' => $result['message'] ?? 'OTP sent successfully.',
            'message' => $result['message'] ?? '驗證碼已成功發送。'
        ];


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
            // 'message' => 'OTP verified successfully',
            'message' => '驗證碼驗證成功'
        ]);
    }

    public function checkPasswordExpiry(Request $request)
    {
        $isExpired = $this->authService->checkPasswordExpiry($request->user());

        return response()->json([
            'password_expired' => $isExpired,
        ]);
    }

    public function updateUser(UpdateUserRequest $request, string $id)
    {
        $data = $request->validated();

        $updatedUser = $this->authService->updateUser($id, $data);

        return response()->json([
            // 'message' => 'User updated successfully',
            'message' => '用戶更新成功',
            'user'    => $updatedUser,
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

        Log::info('user' . $user);

        $data['created_by'] = $user->id;

        $user = $this->authService->createUserByAdmin($data);

        return response()->json([
            // 'message' => 'User created successfully',
            'message' => '用戶建立成功',
            'user'    => $user,
        ], 201);
    }

    public function forgetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => '請輸入電子郵件',
            'email.email'    => '電子郵件格式錯誤',
            'email.exists'   => '請輸入正確帳號/密碼',
        ]);

        $result = $this->authService->forgetPassword($data['email']);

        if ($result['error'] ?? false) {
            return response()->json([
                'message' => $result['error'],
            ], 400);
        }

        return response()->json([
            'message' => $result['message'] ?? '重置密碼通知信已成功發送',
        ]);
    }
}
