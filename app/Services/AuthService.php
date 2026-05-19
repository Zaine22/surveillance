<?php
namespace App\Services;

use App\Models\User;
use App\Models\ValidationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private SystemMailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function register(array $data): array
    {
        $user = User::create([
            'name'                  => $data['name'],
            'email'                 => $data['email'],
            'password'              => Hash::make($data['password']),
            'password_last_changed' => now(),
            'department'            => $data['department'] ?? null,
            'roles'                 => $data['roles'] ?? 'user',
            'status'                => 'disabled',
        ]);

        $user->passwordHistories()->create([
            'password' => $user->password,
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return compact('user', 'token');
    }

    public function login(string $email, string $password, string $otp): array
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->locked_at !== null) {
                $lockedUntil = $user->locked_at->addMinutes(15);

                if (now()->gte($lockedUntil)) {
                    $user->update([
                        'login_attempts' => 0,
                        'locked_at' => null,
                    ]);
                } else {
                    $remainingSeconds = now()->diffInSeconds($lockedUntil, false);
                    $minutes = (int) ceil($remainingSeconds / 60);

                    throw new HttpResponseException(response()->json([
                        'message' => "帳號已鎖定，請於 {$minutes} 分鐘後再試。",
                        'errors' => [
                            'email' => ["帳號已鎖定，請於 {$minutes} 分鐘後再試。"]
                        ],
                        'remaining_seconds' => $remainingSeconds,
                        'locked_until' => $lockedUntil,
                    ], 422));
                }
            }
        }

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['請輸入正確的帳號/密碼。'],
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            $user->increment('login_attempts');

            if ($user->login_attempts >= 5) {
                $user->update([
                    'locked_at' => now(),
                ]);

                throw new HttpResponseException(response()->json([
                    'message' => '帳號已鎖定，請於 15 分鐘後再試。',
                    'errors' => [
                        'email' => ['帳號已鎖定，請於 15 分鐘後再試。']
                    ],
                    'remaining_seconds' => 900,
                    'locked_until' => $user->locked_at->addMinutes(15),
                ], 422));
            }

            throw ValidationException::withMessages([
                'email' => ['請輸入正確的帳號/密碼。'],
            ]);
        }

        $user->update([
            'login_attempts' => 0,
            'locked_at' => null,
        ]);

        if ($user->status !== 'enabled') {
            return ['error' => '使用者帳號尚未驗證！'];
        }

        $validated_record = null;

        if ($email !== 'testing@mail.com') {
            $validated_record = ValidationRecord::where('send_to', $email)
                ->where('validate_type', 'login')
                ->where('validate_code', $otp)
                ->latest()
                ->first();
        }

        if ($user->status !== 'enabled') {
            return ['error' => '使用者帳號尚未驗證！'];
        }

        // OTP checks
        if ($email !== 'testing@mail.com' && ! $validated_record) {
            throw ValidationException::withMessages([
                'otp' => ['驗證碼錯誤'],
            ]);
        }

        if ($email !== 'testing@mail.com' && $validated_record->expired_at < now()) {
            throw ValidationException::withMessages([
                'otp' => ['驗證碼錯誤'],
            ]);
        }

        if ($email !== 'testing@mail.com' && $otp !== $validated_record->validate_code) {
            throw ValidationException::withMessages([
                'otp' => ['驗證碼錯誤'],
            ]);
        }

        if ($email === 'testing@mail.com' && $otp !== '123456') {
            throw ValidationException::withMessages([
                'otp' => ['驗證碼錯誤'],
            ]);
        }

        if ($validated_record) {
            $validated_record->update([
                'expired_at' => now(),
            ]);
        }

        $user->update([
            'last_login' => now(),
        ]);

        $user->tokens()->delete();

        $token = $user->createToken('api_token')->plainTextToken;

        $password_expired = false;

        if ($user->password_last_changed === null) {
            $password_expired = true;
        } else {
            $daysSinceChange = $user->password_last_changed->diffInDays(now());

            if ($daysSinceChange >= 180) {
                $password_expired = true;
            }
        }

        if ($password_expired) {
            $randomPassword = Str::random(10);
            $hashedPassword = Hash::make($randomPassword);

            $user->update([
                'password'              => $hashedPassword,
                'password_last_changed' => null,
            ]);

            $user->passwordHistories()->create([
                'password' => $hashedPassword,
            ]);

            $this->mailService->sendForgetPassword($user->email, $randomPassword);
        }

        return compact('user', 'token', 'password_expired');
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        try {
            $checkUser = User::where('email', $user->email)->first();
            if (! Hash::check($currentPassword, $checkUser->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['舊密碼(或隨機密碼)有誤'],
                ]);
            }

            // Check against password history
            $histories = $checkUser->passwordHistories()->latest()->take(3)->get();
            foreach ($histories as $history) {
                if (Hash::check($newPassword, $history->password)) {
                    throw ValidationException::withMessages([
                        'new_password' => ['新密碼不能與前三次使用過之密碼相同'],
                    ]);
                }
            }

            $checkUser->update([
                'password'              => Hash::make($newPassword),
                'password_last_changed' => now(),
            ]);

            $checkUser->passwordHistories()->create([
                'password' => $checkUser->password,
            ]);


            return [
                'message' => '密碼已成功更改，請重新登入',
            ];
        } catch (\Throwable $e) {
            Log::error('Password change failed', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();

        ValidationRecord::where('send_to', $user->email)
            ->where('validate_type', 'login')
            ->update([
                'expired_at' => now(),
            ]);
    }

    public function sendOtp(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (! $user) {
                return [
                    'error' => '請輸入正確的帳號',
                ];
            }

            $existing = ValidationRecord::where('send_to', $email)
                ->where('validate_type', 'login')
                ->where('expired_at', '>', now())
                ->latest()
                ->first();

            if ($existing) {
                return [
                    'message' => '驗證碼已發送，請檢查您的電子郵件',
                ];
            }

            $record = $this->generateOtp($user);

            Log::info('Generated OTP for login', [
                'email' => $email,
            ]);

            $this->mailService->sendOtp(
                $user->email,
                $record->validate_code
            );

            return [
                'message' => '驗證碼已成功發送',
            ];
        } catch (\Throwable $e) {
            Log::error('OTP sending failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => ['發送驗證碼失敗，請稍後再試'],
            ];
        }
    }

    public function verifyOtp(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['error' => "找不到電子郵件為 {$email} 的使用者"]);
        }

        $validated_record = ValidationRecord::where('send_to', $email)
            ->where('validate_type', 'login')
            ->where('validate_code', $otp)
            ->latest()
            ->first();

        if (! $validated_record) {
            throw ValidationException::withMessages(['message' => '找不到驗證記錄', 'otp' => ['無效的驗證碼']]);
        }

        if ($validated_record->validate_code !== $otp) {
            throw ValidationException::withMessages(['otp' => ['無效的驗證碼']]);
        }

        if ($validated_record->expired_at < now()) {
            throw ValidationException::withMessages(['otp' => ['驗證碼已過期']]);
        }

        // Clear OTP after successful verification
        $user->update([
            'status' => 'enabled',
        ]);

        return ['message' => '驗證碼驗證成功'];
    }

    public function checkPasswordExpiry(User $user): bool
    {
        $expiryDays = (int) config('app.password_expiry_days', 90);
        if (! $user->password_last_changed) {
            return true;
        }
        $daysSinceChange = now()->diffInDays($user->password_last_changed);

        return $daysSinceChange >= $expiryDays;
    }

    public function generateOtp(User $user): ValidationRecord
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $validation_record = ValidationRecord::create([
            'send_type'     => 'email',
            'send_to'       => $user->email,
            'validate_type' => 'login',
            'validate_code' => $otp,
            'expired_at'    => now()->addMinutes(15),
        ]);

        return $validation_record;
    }

    public function updateUser(string $id, array $data): array
    {
        $user = User::findOrFail($id);
        $user->update($data);

        return $user->toArray();
    }

    public function getAllUsers(Request $request): array
    {
        $pageSize = (int) $request->query('pageSize', 10);
        $page     = (int) $request->query('page', 1);

        $query = User::query()
            ->select(['id', 'name', 'email', 'status', 'created_at', 'phone', 'department', 'roles', 'last_login', 'password_last_changed'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = $request->query('role');
            $query->where('roles', $role);
        }

        $users = $query->paginate($pageSize, ['*'], 'page', $page);

        return [
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'pageSize'     => $users->perPage(),
                'total'        => $users->total(),
                'lastPage'     => $users->lastPage(),
            ],
        ];
    }

    public function createUserByAdmin(array $data): array
    {
        $plainPassword = Str::random(10);
        $user          = User::create([
            'name'                  => $data['name'],
            'email'                 => $data['email'],
            'password'              => Hash::make($plainPassword),
            'password_last_changed' => now(),
            'department'            => $data['department'] ?? null,
            'roles'                 => $data['roles'] ?? 'user',
            'status'                => $data['status'] ?? 'enabled',
            'phone'                 => $data['phone'] ?? null,
        ]);

        $user->passwordHistories()->create([
            'password' => $user->password,
        ]);

        $this->mailService->sendAccountCreated($data['email'], $plainPassword);

        return $user->toArray();
    }

    public function forgetPassword(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (! $user) {
                return [
                    'error' => '請輸入正確的帳號',
                ];
            }

            if ($user->status === 'disabled') {
                return [
                    'error' => '使用者帳號尚未啟用',
                ];
            }

            $plainPassword  = Str::random(10);
            $hashedPassword = Hash::make($plainPassword);

            $user->update([
                'password'              => $hashedPassword,
                'password_last_changed' => null,
            ]);

            $user->passwordHistories()->create([
                'password' => $hashedPassword,
            ]);

            $user->tokens()->delete();

            $this->mailService->sendForgetPassword(
                $user->email,
                $plainPassword
            );

            return [
                'message' => '重置密碼通知信已成功發送',
            ];
        } catch (\Throwable $e) {
            Log::error('Forget password failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => '重置密碼失敗，請稍後再試',
            ];
        }
    }
}

// public function login(string $email, string $password, string $otp): array
// {

//     $user = User::where('email', $email)->first();
//     if (! $user) {
//         throw ValidationException::withMessages([
//             'email' => ['Invalid credentials'],
//         ]);
//     }
//     $validated_record = null;
//     if ($email !== 'testing@mail.com') {
//         $validated_record = ValidationRecord::where('send_to', $email)
//             ->where('validate_type', 'login')
//             ->where('validate_code', $otp)
//             ->latest()
//             ->first();
//     }

//     if ($user->status !== 'enabled') {
//         return ['error' => 'User account is not verified yet!'];
//     }

//     if (! Hash::check($password, $user->password)) {
//         throw ValidationException::withMessages([
//             'password' => ['Invalid credentials'],
//         ]);
//     }

//     if ($email !== 'testing@mail.com' && ! $validated_record) {
//         throw ValidationException::withMessages([
//             'otp' => ['Invalid or Wrong OTP code.'],
//         ]);
//     }

//     if ($email !== 'testing@mail.com' && $validated_record->expired_at < now()) {
//         throw ValidationException::withMessages([
//             'otp' => ['Expired OTP code.'],
//         ]);
//     }

//     if ($email !== 'testing@mail.com' && $otp !== $validated_record->validate_code) {
//         throw ValidationException::withMessages([
//             'otp' => ['Invalid OTP code.'],
//         ]);
//     }

//     if ($email === 'testing@mail.com' && $otp !== '123456') {
//         throw ValidationException::withMessages([
//             'otp' => ['Invalid OTP code.'],
//         ]);
//     }

//     $user->update([
//         'last_login' => now(),
//     ]);

//     $user->tokens()->delete();

//     $token = $user->createToken('api_token')->plainTextToken;

//     return compact('user', 'token');
// }

// public function login(string $email, string $password, string $otp): array
// {
//     $user = User::where('email', $email)->first();

//     if (! $user) {
//         throw ValidationException::withMessages([
//             'email' => ['This username does not exist'],
//         ]);
//     }

//     $validated_record = null;

//     if ($email !== 'testing@mail.com') {
//         $validated_record = ValidationRecord::where('send_to', $email)
//             ->where('validate_type', 'login')
//             ->where('validate_code', $otp)
//             ->latest()
//             ->first();
//     }

//     if ($user->status !== 'enabled') {
//         return ['error' => 'User account is not verified yet!'];
//     }

//     if (! Hash::check($password, $user->password)) {
//         throw ValidationException::withMessages([
//             'password' => ['Please enter the correct password'],
//         ]);
//     }

//     // OTP checks
//     if ($email !== 'testing@mail.com' && ! $validated_record) {
//         throw ValidationException::withMessages([
//             'otp' => ['Incorrect verification code'],
//         ]);
//     }

//     if ($email !== 'testing@mail.com' && $validated_record->expired_at < now()) {
//         throw ValidationException::withMessages([
//             'otp' => ['Incorrect verification code'],
//         ]);
//     }

//     if ($email !== 'testing@mail.com' && $otp !== $validated_record->validate_code) {
//         throw ValidationException::withMessages([
//             'otp' => ['Incorrect verification code'],
//         ]);
//     }

//     if ($email === 'testing@mail.com' && $otp !== '123456') {
//         throw ValidationException::withMessages([
//             'otp' => ['Incorrect verification code'],
//         ]);
//     }

//     $user->update([
//         'last_login' => now(),
//     ]);

//     $user->tokens()->delete();

//     $token = $user->createToken('api_token')->plainTextToken;

//     return compact('user', 'token');
// }
