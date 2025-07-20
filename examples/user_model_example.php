<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use LaravelStytch\Contracts\StytchUserContract;
use LaravelStytch\Traits\HasStytchUser;
use LaravelStytch\Traits\StytchAuthenticatable;

/**
 * Example User model demonstrating proper Stytch integration.
 * 
 * This model shows how to combine Laravel's standard authentication features
 * with Stytch's authentication system by implementing the Authenticatable
 * contract and using traits to satisfy the contract requirements.
 */
class User extends Model implements Authenticatable, StytchUserContract
{
    use HasApiTokens, HasFactory, Notifiable, HasStytchUser, StytchAuthenticatable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'stytch_user_id',
        'email_verified_at',
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

    /**
     * Get the user's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email;
    }

    /**
     * Check if the user has a Stytch user ID.
     */
    public function hasStytchAccount(): bool
    {
        return !empty($this->getStytchUserId());
    }

    /**
     * Get the user's primary email address.
     */
    public function getPrimaryEmail(): ?string
    {
        return $this->getStytchEmail();
    }

    /**
     * Update user data from Stytch user object.
     */
    public function updateFromStytch(array $stytchUser): void
    {
        // Update Stytch user ID if not set
        if (!$this->getStytchUserId()) {
            $this->setStytchUserId($stytchUser['user_id']);
        }

        // Update email if available
        if (isset($stytchUser['emails']) && !empty($stytchUser['emails'])) {
            $primaryEmail = $stytchUser['emails'][0] ?? null;
            if ($primaryEmail && isset($primaryEmail['email'])) {
                $this->setStytchEmail($primaryEmail['email']);
            }
        }

        // Update name if available
        if (isset($stytchUser['name'])) {
            $newName = '';
            if (is_array($stytchUser['name'])) {
                $firstName = $stytchUser['name']['first_name'] ?? '';
                $lastName = $stytchUser['name']['last_name'] ?? '';
                $newName = trim($firstName . ' ' . $lastName);
            } else {
                $newName = $stytchUser['name'];
            }
            
            if ($newName && $this->name !== $newName) {
                $this->name = $newName;
            }
        }

        // Mark email as verified if it comes from Stytch
        if (isset($stytchUser['emails']) && !empty($stytchUser['emails'])) {
            $primaryEmail = $stytchUser['emails'][0] ?? null;
            if ($primaryEmail && isset($primaryEmail['verified']) && $primaryEmail['verified']) {
                $this->email_verified_at = now();
            }
        }

        $this->save();
    }

    /**
     * Scope to find users with Stytch accounts.
     */
    public function scopeWithStytchAccount($query)
    {
        return $query->whereNotNull($this->getStytchUserIdColumn());
    }

    /**
     * Scope to find users without Stytch accounts.
     */
    public function scopeWithoutStytchAccount($query)
    {
        return $query->whereNull($this->getStytchUserIdColumn());
    }
} 