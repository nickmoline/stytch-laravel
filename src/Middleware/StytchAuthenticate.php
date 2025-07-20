<?php

namespace LaravelStytch\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use LaravelStytch\Contracts\StytchUserContract;
use LaravelStytch\Facades\Stytch;
use LaravelStytch\Traits\HasStytchUser;
use LaravelStytch\Traits\StytchAuthenticatable;
use Symfony\Component\HttpFoundation\Response;

class StytchAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // First, check if the user is already logged in via Laravel sessions
        if (Auth::check()) {
            // User is already authenticated, ensure the user object is available in the request
            $request->setUserResolver(function () {
                return Auth::user();
            });
            
            return $next($request);
        }

        // User is not logged in, check for Stytch cookies
        $sessionToken = $request->cookie(config('stytch.session_cookie_name', 'stytch_session'));
        $jwtToken = $request->cookie(config('stytch.jwt_cookie_name', 'stytch_session_jwt'));

        if (!$sessionToken && !$jwtToken) {
            // No Stytch cookies found, continue without authentication
            return $next($request);
        }

        try {
            // Attempt to authenticate with Stytch
            $stytchUser = $this->authenticateWithStytch($sessionToken, $jwtToken);
            
            if (!$stytchUser) {
                // Authentication failed, continue without authentication
                return $next($request);
            }

            // Find or create the user in our system
            $user = $this->findOrCreateUser($stytchUser);
            
            if (!$user) {
                Log::error('Failed to find or create user for Stytch user ID: ' . ($stytchUser['user_id'] ?? 'unknown'));
                return $next($request);
            }

            // Log the user in with Laravel's Auth system
            Auth::login($user);
            
            // Ensure the user object is available in the request
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            Log::info('User authenticated via Stytch middleware', [
                'user_id' => $user->getAuthIdentifier(),
                'stytch_user_id' => $stytchUser['user_id'] ?? null,
                'auth_method' => config('stytch.default_auth_method', 'b2c')
            ]);

        } catch (\Exception $e) {
            Log::error('Stytch authentication failed in middleware: ' . $e->getMessage(), [
                'exception' => $e,
                'auth_method' => config('stytch.default_auth_method', 'b2c')
            ]);
        }

        return $next($request);
    }

    /**
     * Authenticate with Stytch using session token or JWT.
     */
    protected function authenticateWithStytch(?string $sessionToken, ?string $jwtToken): ?array
    {
        $authMethod = config('stytch.default_auth_method', 'b2c');
        
        try {
            if ($sessionToken) {
                if ($authMethod === 'b2b') {
                    $response = Stytch::b2b()->sessions->authenticate(['session_token' => $sessionToken]);
                } else {
                    $response = Stytch::b2c()->sessions->authenticate(['session_token' => $sessionToken]);
                }
                return $response->toArray();
            } elseif ($jwtToken) {
                if ($authMethod === 'b2b') {
                    // B2B doesn't have authenticateJwt method, so we'll return null
                    Log::warning('JWT authentication not supported for B2B in middleware');
                    return null;
                } else {
                    $response = Stytch::b2c()->sessions->authenticateJwt(['session_jwt' => $jwtToken]);
                    // authenticateJwt returns { session: Session; session_jwt: string }
                    // We need to get the user from the session
                    $session = $response['session'] ?? null;
                    if ($session && isset($session['user_id'])) {
                        // Get the user details from Stytch
                        $userResponse = Stytch::b2c()->users->get((string) $session['user_id']);
                        return $userResponse;
                    }
                    return null;
                }
            }
        } catch (\Exception $e) {
            Log::error("Stytch {$authMethod} authentication failed: " . $e->getMessage());
            return null;
        }

        return null;
    }

    /**
     * Find or create a user based on Stytch user data.
     */
    protected function findOrCreateUser(array $stytchUser): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        $userModel = config('stytch.user_model', 'User');
        
        if (!class_exists($userModel)) {
            Log::error("User model {$userModel} does not exist");
            return null;
        }

        // Check if the model uses our required traits and implements the contract
        $usesStytchUserTrait = in_array(HasStytchUser::class, class_uses_recursive($userModel));
        $usesStytchAuthenticatableTrait = in_array(StytchAuthenticatable::class, class_uses_recursive($userModel));
        $implementsStytchUserContract = in_array(StytchUserContract::class, class_implements($userModel));
        
        if (!$usesStytchUserTrait) {
            Log::error("User model {$userModel} must use the HasStytchUser trait");
            return null;
        }

        if (!$usesStytchAuthenticatableTrait) {
            Log::error("User model {$userModel} must use the StytchAuthenticatable trait");
            return null;
        }

        if (!$implementsStytchUserContract) {
            Log::error("User model {$userModel} must implement the StytchUserContract");
            return null;
        }

        $stytchUserId = $stytchUser['user_id'] ?? null;
        if (!$stytchUserId) {
            Log::error('No user_id found in Stytch response');
            return null;
        }

        // Try to find user by Stytch user ID
        $user = $userModel::stytchUserId($stytchUserId)->first();
        
        if ($user) {
            // Update user data if needed
            if ($user instanceof StytchUserContract) {
                $this->updateUserFromStytchResponse($user, $stytchUser);
            }
            return $user;
        }

        // User doesn't exist, create a new one
        $user = $this->createUserFromStytchResponse($stytchUser);
        
        if ($user && $user instanceof \Illuminate\Database\Eloquent\Model) {
            $user->save();
        }

        return $user;
    }

    /**
     * Update user data from Stytch response.
     */
    protected function updateUserFromStytchResponse(StytchUserContract $user, array $stytchUserData): void
    {
        $authMethod = config('stytch.default_auth_method', 'b2c');
        
        if ($authMethod === 'b2b') {
            // B2B Member object structure
            if (isset($stytchUserData['email_address'])) {
                $currentEmail = $user->getStytchEmail();
                if ($currentEmail !== $stytchUserData['email_address']) {
                    $user->setStytchEmail($stytchUserData['email_address']);
                }
            }
        } else {
            // B2C User object structure
            if (isset($stytchUserData['emails']) && !empty($stytchUserData['emails'])) {
                $primaryEmail = $stytchUserData['emails'][0] ?? null;
                if ($primaryEmail && isset($primaryEmail['email'])) {
                    $currentEmail = $user->getStytchEmail();
                    if ($currentEmail !== $primaryEmail['email']) {
                        $user->setStytchEmail($primaryEmail['email']);
                    }
                }
            }
        }

        // Update name if available and different
        if ($authMethod === 'b2b') {
            // B2B Member: name is a string
            if (isset($stytchUserData['name'])) {
                $newName = $stytchUserData['name'];
                $currentName = $user->getStytchName();
                if ($newName && $currentName !== $newName) {
                    $user->updateStytchName($newName);
                }
            }
        } else {
            // B2C User: name is an object with first_name and last_name
            if (isset($stytchUserData['name']) && is_array($stytchUserData['name'])) {
                $firstName = $stytchUserData['name']['first_name'] ?? '';
                $lastName = $stytchUserData['name']['last_name'] ?? '';
                $newName = trim($firstName . ' ' . $lastName);
                $currentName = $user->getStytchName();
                if ($newName && $currentName !== $newName) {
                    $user->updateStytchName($newName);
                }
            }
        }

        // Save the user if any changes were made
        if ($user instanceof \Illuminate\Database\Eloquent\Model) {
            $user->save();
        }
    }

    /**
     * Create a new user from Stytch response.
     */
    protected function createUserFromStytchResponse(array $stytchUser): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        $userModel = config('stytch.user_model', 'User');
        $authMethod = config('stytch.default_auth_method', 'b2c');
        
        $userData = [
            'stytch_user_id' => $stytchUser['user_id'] ?? null,
        ];

        try {
            $user = new $userModel($userData);
            
            if ($user instanceof StytchUserContract) {
                if ($authMethod === 'b2b') {
                    // B2B Member object structure
                    if (isset($stytchUser['email_address'])) {
                        $user->setStytchEmail($stytchUser['email_address']);
                    }
                } else {
                    // B2C User object structure
                    if (isset($stytchUser['emails']) && !empty($stytchUser['emails'])) {
                        $primaryEmail = $stytchUser['emails'][0] ?? null;
                        if ($primaryEmail && isset($primaryEmail['email'])) {
                            $user->setStytchEmail($primaryEmail['email']);
                        }
                    }
                }

                // Set name using contract method
                if ($authMethod === 'b2b') {
                    // B2B Member: name is a string
                    if (isset($stytchUser['name'])) {
                        $user->updateStytchName($stytchUser['name']);
                    }
                } else {
                    // B2C User: name is an object with first_name and last_name
                    if (isset($stytchUser['name']) && is_array($stytchUser['name'])) {
                        $firstName = $stytchUser['name']['first_name'] ?? '';
                        $lastName = $stytchUser['name']['last_name'] ?? '';
                        $newName = trim($firstName . ' ' . $lastName);
                        $user->updateStytchName($newName);
                    }
                }
            }

            return $user;
        } catch (\Exception $e) {
            Log::error('Failed to create user from Stytch response: ' . $e->getMessage());
            return null;
        }
    }
} 