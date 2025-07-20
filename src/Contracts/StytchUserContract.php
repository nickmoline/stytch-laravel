<?php

namespace LaravelStytch\Contracts;

/**
 * Contract for user models that can be updated with Stytch user data.
 * 
 * This contract provides methods for updating user data from Stytch responses
 * without making assumptions about the specific field names in the user model.
 */
interface StytchUserContract
{
    /**
     * Get the user's current email address.
     * 
     * @return string|null The current email address
     */
    public function getStytchEmail(): ?string;

    /**
     * Set the user's email address.
     * 
     * @param string $email The email address
     * @return void
     */
    public function setStytchEmail(string $email): void;

    /**
     * Update the user's name from Stytch data.
     * 
     * @param string $name The name from Stytch
     * @return void
     */
    public function updateStytchName(string $name): void;

    /**
     * Get the user's current name.
     * 
     * @return string|null The current name
     */
    public function getStytchName(): ?string;
} 