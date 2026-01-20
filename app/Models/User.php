<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /** 
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'password',
        'password_last_changed',
        'name',
        'email',
        'phone',
        'department',
        'roles',
        'status',
        'last_login',
        'otp',
        'otp_created_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'password_last_changed' => 'datetime',
        'last_login' => 'datetime',
    ];
}
