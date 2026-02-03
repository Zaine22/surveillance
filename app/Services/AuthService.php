<?php

namespace App\Services;

use App\Models\User;
use App\Models\ValidationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'password_last_changed' => now(),
            'department' => $data['department'] ?? null,
            'roles' => $data['roles'] ?? 'user',
            'status' => 'disabled',
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return compact('user', 'token');
    }

    public function login(string $email, string $password, string $otp): array
    {

        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $validated_record = ValidationRecord::where('send_to', $email)
            ->where('validate_type', 'login')
            ->where('validate_code', $otp)
            ->latest()
            ->first();

        if ($user->status !== 'enabled') {
            return ['error' => 'User account is not verified yet!'];
        }

        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Invalid credentials'],
            ]);
        }

        if (! $validated_record) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or Wrong OTP code.'],
            ]);
        }

        if ($validated_record->expired_at < now()) {
            throw ValidationException::withMessages([
                'otp' => ['Expired OTP code.'],
            ]);
        }

        if ($otp !== $validated_record->validate_code) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP code.'],
            ]);
        }

        $user->update([
            'last_login' => now(),
        ]);

        $user->tokens()->delete();

        $token = $user->createToken('api_token')->plainTextToken;

        return compact('user', 'token');
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
            'password_last_changed' => now(),
        ]);

        $user->tokens()->delete();
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    public function sendOtp(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();
            if (! $user) {
                throw ValidationException::withMessages(['error' => "User with email {$email} not found."]);
            }
            $validated_record = ValidationRecord::where('send_to', $email)
                ->where('validate_type', 'login')
                ->where('expired_at', '>', now())
                ->latest()
                ->first();

            if ($validated_record) {
                return ['message' => 'OTP already sent. Please check your email or wait for a while to get new otp.', 'otp' => $validated_record->validate_code];
            }
            $result = $this->generateOtp($user);
            Log::info("Generated OTP for user {$email}", ['otp' => $result->validate_code]);
            $this->mailService->sendOtp($user->email, $result->validate_code);

            return ['otp' => $result->validate_code];
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email: '.$e->getMessage());

            return [
                'error' => ['Failed to send OTP email. Please try again later.'],
            ];
        }
    }

    public function verifyOtp(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['error' => "User with email {$email} not found."]);
        }

        $validated_record = ValidationRecord::where('send_to', $email)
            ->where('validate_type', 'login')
            ->where('validate_code', $otp)
            ->latest()
            ->first();

        if (! $validated_record) {
            throw ValidationException::withMessages(['message' => 'No Record found', 'otp' => ['Invalid OTP code.']]);
        }

        if ($validated_record->validate_code !== $otp) {
            throw ValidationException::withMessages(['otp' => ['Invalid OTP code.']]);
        }

        if ($validated_record->expired_at < now()) {
            throw ValidationException::withMessages(['otp' => ['OTP code has expired.']]);
        }

        // Clear OTP after successful verification
        $user->update([
            'status' => 'enabled',
        ]);

        return ['message' => 'OTP verified successfully.'];
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
            'send_type' => 'email',
            'send_to' => $user->email,
            'validate_type' => 'login',
            'validate_code' => $otp,
            'expired_at' => now()->addMinutes(5),
        ]);

        return $validation_record;
    }

    public function updateUser(User $user, array $data): array
    {
        $user->update($data);

        return $user->toArray();
    }

    public function getAllUsers(Request $request): array
    {
        $pageSize = (int) $request->query('pageSize', 10);
        $page = (int) $request->query('page', 1);

        $users = User::query()
            ->select(['id', 'name', 'email', 'status', 'created_at'])
            ->orderByDesc('created_at')
            ->paginate($pageSize, ['*'], 'page', $page);

        return [
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'pageSize' => $users->perPage(),
                'total' => $users->total(),
                'lastPage' => $users->lastPage(),
            ],
        ];
    }
}
