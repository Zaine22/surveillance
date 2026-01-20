<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\SystemMailService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            'status'                => 'disabled',
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return compact('user', 'token');
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        if ($user->status !== 'enabled') {
            return ['error' => 'User account is not verified yet!',];
        }

        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Invalid credentials'],
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
            'password'              => Hash::make($newPassword),
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
            $result = $this->generateOtp($user);
            Log::info("Generated OTP for user {$email}", ['otp' => $result['otp']]);
            $this->mailService->sendOtp($user->email, $result['otp']);
            return ['otp' => $result['otp']];
        } catch (\Exception $e) {
            Log::error("Failed to send OTP email: " . $e->getMessage());
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

        if ($user->otp !== $otp) {
            throw ValidationException::withMessages(['otp' => ['Invalid OTP code.']]);
        }

        $otpAge = now()->diffInMinutes($user->otp_created_at);
        if ($otpAge > 5) {
            throw ValidationException::withMessages(['otp' => ['OTP code has expired.']]);
        }

        // Clear OTP after successful verification
        $user->update([
            'otp' => null,
            'otp_created_at' => null,
            'status' => 'enabled',
        ]);

        return ['message' => 'OTP verified successfully.'];
    }

    public function generateOtp(User $user): User
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp'             => $otp,
            'otp_created_at'  => now(),
        ]);

        return $user;
    }
}
