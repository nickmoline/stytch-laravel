<?php

namespace LaravelStytch\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;

trait HasStytchUser
{
    /**
     * Get the Stytch user ID column name.
     */
    public function getStytchUserIdColumn(): string
    {
        return config('stytch.stytch_user_id_column', 'stytch_user_id');
    }

    /**
     * Get the Stytch email column name.
     */
    public function getStytchEmailColumn(): string
    {
        return config('stytch.email_column', 'email');
    }

    /**
     * Get the Stytch user ID.
     */
    public function getStytchUserId(): ?string
    {
        $column = $this->getStytchUserIdColumn();
        return $this->{$column};
    }

    /**
     * Set the Stytch user ID.
     */
    public function setStytchUserId(string $stytchUserId): void
    {
        $column = $this->getStytchUserIdColumn();
        $this->{$column} = $stytchUserId;
    }

    /**
     * Get the user's Stytch email.
     */
    public function getStytchEmail(): ?string
    {
        $column = $this->getStytchEmailColumn();
        return $this->{$column};
    }

    /**
     * Set the user's Stytch email.
     */
    public function setStytchEmail(string $email): void
    {
        $column = $this->getStytchEmailColumn();
        $this->{$column} = $email;
    }

    /**
     * Scope to find a user by Stytch user ID.
     */
    #[Scope]
    public function stytchUserId(Builder $query, string $stytchUserId): void
    {
        $column = $this->getStytchUserIdColumn();
        $query->where($column, $stytchUserId);
    }

    /**
     * Scope to find a user by Stytch email.
     */
    #[Scope]
    public function stytchEmail(Builder $query, string $email): void
    {
        $column = $this->getStytchEmailColumn();
        $query->where($column, $email);
    }




}
