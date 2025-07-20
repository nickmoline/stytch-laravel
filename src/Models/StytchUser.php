<?php

namespace LaravelStytch\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LaravelStytch\Contracts\StytchUserContract;
use LaravelStytch\Traits\HasStytchUser;
use LaravelStytch\Traits\StytchAuthenticatable;

/**
 * Base User model for Stytch authentication.
 * 
 * This model demonstrates how to properly implement Stytch authentication
 * in Laravel by implementing the Authenticatable contract and using traits
 * to satisfy the contract requirements.
 */
class StytchUser extends Model implements Authenticatable, StytchUserContract
{
    use HasStytchUser, StytchAuthenticatable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'stytch_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
} 