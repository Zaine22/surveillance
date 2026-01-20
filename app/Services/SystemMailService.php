<?php

namespace App\Services;

use App\Mail\SystemMail;
use Illuminate\Support\Facades\Mail;

class SystemMailService
{
    public function sendOtp(string $email, string $otp): void
    {
        Mail::to($email)->send(
            new SystemMail(
                subjectText: 'Your OTP Code',
                viewname: 'emails.otp',
                data: [
                    'otp' => $otp,
                    'createdAt' => now()->format('Y-m-d H:i:s'),
                    'expiresIn' => 5,
                ]
            )
        );
    }

    public function sendNotification(
        string $email,
        string $title,
        string $message
    ): void {
        Mail::to($email)->queue(
            new SystemMail(
                subjectText: $title,
                viewname: 'emails.notification',
                data: [
                    'title' => $title,
                    'message' => $message,
                ]
            )
        );
    }
}
