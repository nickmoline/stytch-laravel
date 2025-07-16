<?php

namespace LaravelStytch\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Log;
use LaravelStytch\Facades\Stytch;
use LaravelStytch\Traits\HasStytchUser;
use LaravelStytch\Traits\StytchAuthenticatable;

class StytchB2CUserServiceProvider implements UserProvider
{
    /**
     * The user model class.
     */
    protected string $model;

    /**
     * Create a new Stytch B2C user provider.
     */
    public function __construct(string $model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        if (!class_exists($this->model)) {
            return null;
        }

        return $this->model::find($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        if (!class_exists($this->model)) {
            return null;
        }

        $user = $this->model::find($identifier);

        if ($user && $user->getRememberToken() && hash_equals($user->getRememberToken(), $token)) {
            return $user;
        }

        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->setRememberToken($token);

        if (method_exists($user, 'save')) {
            $user->save();
        }
    }

    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) || !isset($credentials['email'])) {
            return null;
        }

        if (!class_exists($this->model)) {
            return null;
        }

                // Check if the model uses our required traits
        $usesStytchUserTrait = in_array(HasStytchUser::class, class_uses_recursive($this->model));
        $usesStytchAuthenticatableTrait = in_array(StytchAuthenticatable::class, class_uses_recursive($this->model));
        
        if (!$usesStytchUserTrait) {
            Log::warning("User model {$this->model} must use the HasStytchUser trait");
            return null;
        }

        if (!$usesStytchAuthenticatableTrait) {
            Log::warning("User model {$this->model} must use the StytchAuthenticatable trait");
            return null;
        }

        // Try to find user by email
        return $this->model::stytchEmail($credentials['email'])->first();
    }

    /**
     * Validate a user against the given credentials.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (empty($credentials) || !isset($credentials['email']) || !isset($credentials['password'])) {
            return false;
        }

        try {
            // Authenticate with Stytch B2C passwords
            $response = Stytch::b2c()->passwords->authenticate([
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ]);

            // Check if authentication was successful
            if (isset($response['user_id']) && $response['user_id'] === $user->getStytchUserId()) {
                // Update user data if needed
                $this->updateUserFromStytchResponse($user, $response);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error("Stytch B2C password authentication failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user data from Stytch response.
     */
    protected function updateUserFromStytchResponse(Authenticatable $user, array $response): void
    {
        // Update email if available and different
        if (isset($response['user']['emails']) && !empty($response['user']['emails'])) {
            $primaryEmail = $response['user']['emails'][0] ?? null;
            if ($primaryEmail && isset($primaryEmail['email'])) {
                $currentEmail = $user->getStytchEmail();
                if ($currentEmail !== $primaryEmail['email']) {
                    $user->setStytchEmail($primaryEmail['email']);
                }
            }
        }

        // Update name if available and different
        if (isset($response['user']['name'])) {
            $newName = '';
            if (is_array($response['user']['name'])) {
                $firstName = $response['user']['name']['first_name'] ?? '';
                $lastName = $response['user']['name']['last_name'] ?? '';
                $newName = trim($firstName . ' ' . $lastName);
            } else {
                $newName = $response['user']['name'];
            }

            if ($newName && $user->name !== $newName) {
                $user->name = $newName;
            }
        }

        // Save the user if any changes were made
        if (method_exists($user, 'save')) {
            $user->save();
        }
    }

    /**
     * Rehash the user's password if required and supported.
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // For Stytch authentication, we don't need to rehash passwords
        // as Stytch handles password hashing and validation
        // This method is required by the interface but not applicable for Stytch
    }
}
