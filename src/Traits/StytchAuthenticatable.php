<?php

namespace LaravelStytch\Traits;

/**
 * Trait that provides the Laravel Authenticatable contract methods for Stytch users.
 * 
 * This trait provides all the required methods for Laravel's authentication system
 * to work with Stytch-authenticated users. It should be used in conjunction with
 * the HasStytchUser trait.
 * 
 * The model using this trait should implement Illuminate\Contracts\Auth\Authenticatable
 * and use this trait to satisfy the contract requirements.
 */
trait StytchAuthenticatable
{
        /**
     * The column name of the password field using during authentication.
     *
     * @var string
     */
    protected $authPasswordName = 'password';

    /**
     * The column name of the "remember me" token.
     *
     * @var string
     */
    protected $rememberTokenName = 'remember_token';

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier(): mixed
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    /**
     * Get the name of the password field for the user.
     */
    public function getAuthPasswordName(): string
    {
        return $this->authPasswordName;
    }

    /**
     * Get the password for the user.
     * 
     * For Stytch authentication, this method returns null since passwords
     * are managed by Stytch and not stored locally.
     */
    public function getAuthPassword(): ?string
    {
        // For Stytch authentication, passwords are not stored locally
        // They are validated through Stytch's API
        return null;
    }

    /**
     * Get the token value for the "remember me" session.
     */
    public function getRememberToken(): ?string
    {
        if (! empty($this->getRememberTokenName())) {
            return (string) $this->{$this->getRememberTokenName()};
        }
    }

    /**
     * Set the token value for the "remember me" session.
     */
    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        if (! empty($this->getRememberTokenName())) {
            $this->{$this->getRememberTokenName()} = $value;
        }
    }

    /**
     * Get the column name for the "remember me" token.
     */
    public function getRememberTokenName(): string
    {
        return $this->rememberTokenName;
    }
} 