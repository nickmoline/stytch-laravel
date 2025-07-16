<?php

namespace LaravelStytch\Guards;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use LaravelStytch\Facades\Stytch;
use LaravelStytch\Traits\HasStytchUser;
use LaravelStytch\Traits\HasStytchOrganization;

class StytchGuard implements StatefulGuard
{
    /**
     * The user provider implementation.
     */
    protected UserProvider $provider;

    /**
     * The request instance.
     */
    protected Request $request;

    /**
     * The name of the guard.
     */
    protected string $name;

    /**
     * The currently authenticated user.
     */
    protected ?Authenticatable $user = null;

    /**
     * The Stytch client type (b2b or b2c).
     */
    protected string $clientType;

    /**
     * Create a new authentication guard.
     */
    public function __construct(
        string $name,
        UserProvider $provider,
        Request $request,
        string $clientType = 'b2c',
    ) {
        $this->name = $name;
        $this->provider = $provider;
        $this->request = $request;
        $this->clientType = $clientType;
    }

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        return $this->user = $this->authenticateUser();
    }

    /**
     * Get the ID for the currently authenticated user.
     */
    public function id(): int|string|null
    {
        if ($user = $this->user()) {
            return $user->getAuthIdentifier();
        }

        return null;
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        // This method is not typically used for token-based authentication
        // but we can implement it if needed for specific use cases
        return false;
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     */
    public function attempt(array $credentials = [], $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);
            return true;
        }

        return false;
    }

    /**
     * Log a user into the application without sessions or cookies.
     */
    public function once(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            $this->setUser($user);
            return true;
        }

        return false;
    }

    /**
     * Log a user into the application.
     */
    public function login(Authenticatable $user, $remember = false): void
    {
        $this->setUser($user);

        if ($remember) {
            $this->ensureRememberTokenIsSet($user);
            $this->queueRememberCookie($user);
        }

        // Create Stytch session data
        $this->createStytchSessionFromUser($user);
    }

    /**
     * Log the given user ID into the application.
     */
    public function loginUsingId($id, $remember = false): Authenticatable|false
    {
        $user = $this->provider->retrieveById($id);

        if ($user) {
            $this->login($user, $remember);
            return $user;
        }

        return false;
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     */
    public function onceUsingId($id): Authenticatable|false
    {
        $user = $this->provider->retrieveById($id);

        if ($user) {
            $this->setUser($user);
            return $user;
        }

        return false;
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     */
    public function viaRemember(): bool
    {
        // For Stytch authentication, we don't use traditional remember me
        // but we can check if the user was authenticated via session
        return Session::has('stytch.user_id');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(): void
    {
        $user = $this->user();

        if ($user) {
            $this->clearStytchSession();
            $this->setUserToNull();
        }
    }

    /**
     * Check if the user has valid credentials.
     */
    protected function hasValidCredentials($user, array $credentials): bool
    {
        return $user && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Ensure the remember token is set on the user.
     */
    protected function ensureRememberTokenIsSet(Authenticatable $user): void
    {
        if (empty($user->getRememberToken())) {
            $this->cycleRememberToken($user);
        }
    }

    /**
     * Queue the remember cookie for the user.
     */
    protected function queueRememberCookie(Authenticatable $user): void
    {
        // For Stytch authentication, we handle remember me through Stytch sessions
        // This method is kept for compatibility but doesn't set traditional cookies
    }

    /**
     * Create Stytch session data from a user.
     */
    protected function createStytchSessionFromUser(Authenticatable $user): void
    {
        $sessionData = [
            'user_id' => $user->getAuthIdentifier(),
            'authenticated_at' => time(),
            'client_type' => $this->clientType,
        ];

        // Add basic user information
        if (isset($user->name)) {
            $sessionData['name'] = $user->name;
        }

        // Store in Laravel session
        Session::put('stytch', $sessionData);
        
        // Regenerate session ID for security
        Session::regenerate();
    }

    /**
     * Cycle the remember token for the user.
     */
    protected function cycleRememberToken(Authenticatable $user): void
    {
        $token = Str::random(60);
        $this->provider->updateRememberToken($user, $token);
    }

    /**
     * Determine if the current user is authenticated.
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Determine if the current user is a guest.
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Determine if the guard has a user instance.
     */
    public function hasUser(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Set the current user.
     */
    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }

    /**
     * Set the current user to null.
     */
    public function setUserToNull(): void
    {
        $this->user = null;
    }

    /**
     * Get the user provider used by the guard.
     */
    public function getProvider(): UserProvider
    {
        return $this->provider;
    }

    /**
     * Set the user provider used by the guard.
     */
    public function setProvider(UserProvider $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * Get the name of the guard.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the guard.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the request instance.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Set the request instance.
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Authenticate a user based on Stytch tokens or existing session.
     */
    protected function authenticateUser(): ?Authenticatable
    {
        // First, check if we have a valid Laravel session with Stytch data
        if ($this->hasValidStytchSession()) {
            return $this->getUserFromSession();
        }

        // Try to get session token from cookie
        $sessionToken = $this->request->cookie(config('stytch.session_cookie_name', 'stytch_session'));
        $jwtToken = $this->request->cookie(config('stytch.jwt_cookie_name', 'stytch_session_jwt'));

        if (!$sessionToken && !$jwtToken) {
            return null;
        }

        try {
            // Authenticate with Stytch
            $stytchUser = null;
            $stytchMember = null;

            if ($sessionToken) {
                $response = $this->authenticateWithSession($sessionToken);
                $stytchUser = $response['user'] ?? null;
                $stytchMember = $response['member'] ?? null;
            } elseif ($jwtToken) {
                $response = $this->authenticateWithJwt($jwtToken);
                $stytchUser = $response['user'] ?? null;
                $stytchMember = $response['member'] ?? null;
            }

            if (!$stytchUser) {
                return null;
            }

            // Find or create user in our system
            $user = $this->findOrCreateUser($stytchUser);

            if (!$user) {
                return null;
            }

            // Handle organization if member data is available (B2B only)
            if ($this->clientType === 'b2b' && $stytchMember && config('stytch.organization.enabled', false)) {
                $this->handleOrganization($user, $stytchMember);
            }

            // Create Laravel session with Stytch data
            $this->createStytchSession($user, $stytchUser, $stytchMember);

            return $user;

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Stytch {$this->clientType} authentication failed: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Check if we have a valid Stytch session in Laravel's session.
     */
    protected function hasValidStytchSession(): bool
    {
        return Session::has('stytch.user_id') &&
               Session::has('stytch.authenticated_at') &&
               $this->isSessionNotExpired();
    }

    /**
     * Check if the Stytch session has not expired.
     */
    protected function isSessionNotExpired(): bool
    {
        $authenticatedAt = Session::get('stytch.authenticated_at');
        $sessionTimeout = config('stytch.session_timeout', 3600); // 1 hour default

        return (time() - $authenticatedAt) < $sessionTimeout;
    }

    /**
     * Get user from Laravel session.
     */
    protected function getUserFromSession(): ?Authenticatable
    {
        $userId = Session::get('stytch.user_id');

        if (!$userId) {
            return null;
        }

        $userModelClass = config('stytch.user_model', 'App\Models\User');

        if (!class_exists($userModelClass)) {
            return null;
        }

        return $userModelClass::find($userId);
    }

    /**
     * Create Laravel session with Stytch authentication data.
     */
    protected function createStytchSession($user, array $stytchUser, ?array $stytchMember = null): void
    {
        $sessionData = [
            'user_id' => $user->getAuthIdentifier(),
            'stytch_user_id' => $stytchUser['user_id'],
            'authenticated_at' => time(),
            'client_type' => $this->clientType,
        ];

        // Add user details
        if (isset($stytchUser['emails']) && !empty($stytchUser['emails'])) {
            $sessionData['email'] = $stytchUser['emails'][0]['email'] ?? null;
        }

        if (isset($stytchUser['name'])) {
            if (is_array($stytchUser['name'])) {
                $sessionData['name'] = trim(($stytchUser['name']['first_name'] ?? '') . ' ' . ($stytchUser['name']['last_name'] ?? ''));
            } else {
                $sessionData['name'] = $stytchUser['name'];
            }
        }

        // Add B2B member and organization data
        if ($this->clientType === 'b2b' && $stytchMember) {
            $sessionData['member_id'] = $stytchMember['member_id'] ?? null;
            $sessionData['organization_id'] = $stytchMember['organization_id'] ?? null;

            if (isset($stytchMember['organization'])) {
                $sessionData['organization_name'] = $stytchMember['organization']['organization_name'] ?? null;
                $sessionData['organization_slug'] = $stytchMember['organization']['organization_slug'] ?? null;
            }

            // Add member details
            if (isset($stytchMember['member'])) {
                $sessionData['member_email'] = $stytchMember['member']['email_address'] ?? null;
                $sessionData['member_status'] = $stytchMember['member']['status'] ?? null;
            }
        }

        // Store in Laravel session
        Session::put('stytch', $sessionData);

        // Regenerate session ID for security
        Session::regenerate();
    }

    /**
     * Clear Stytch session data.
     */
    public function clearStytchSession(): void
    {
        Session::forget('stytch');
    }

    /**
     * Get Stytch session data.
     */
    public function getStytchSessionData(): ?array
    {
        return Session::get('stytch');
    }

    /**
     * Authenticate with Stytch using session token.
     */
    protected function authenticateWithSession(string $sessionToken): array
    {
        if ($this->clientType === 'b2b') {
            $response = Stytch::b2b()->sessions->authenticate(['session_token' => $sessionToken]);
            return $response->toArray();
        } else {
            return Stytch::b2c()->sessions->authenticate(['session_token' => $sessionToken]);
        }
    }

    /**
     * Authenticate with Stytch using JWT token.
     */
    protected function authenticateWithJwt(string $jwtToken): array
    {
        if ($this->clientType === 'b2b') {
            // B2B doesn't have authenticateJwt method, so we'll return null
            // This should be handled by the calling code
            return [];
        } else {
            return Stytch::b2c()->sessions->authenticateJwt(['session_jwt' => $jwtToken]);
        }
    }

    /**
     * Find or create a user based on Stytch user data.
     */
    protected function findOrCreateUser(array $stytchUser): ?Authenticatable
    {
        $userModelClass = config('stytch.user_model', 'App\Models\User');

        if (!class_exists($userModelClass)) {
            throw new \Exception("User model class {$userModelClass} not found");
        }

        // Check if the model uses our trait
        $usesTrait = in_array(HasStytchUser::class, class_uses_recursive($userModelClass));

        if (!$usesTrait) {
            throw new \Exception("User model {$userModelClass} must use the HasStytchUser trait");
        }

        // Try to find existing user by Stytch user ID
        $user = $userModelClass::stytchUserId($stytchUser['user_id'])->first();

        if ($user) {
            // Update user data if needed
            $this->updateUserFromStytch($user, $stytchUser);
            return $user;
        }

        // Try to find by email if available
        if (isset($stytchUser['emails']) && !empty($stytchUser['emails'])) {
            $primaryEmail = $stytchUser['emails'][0] ?? null;
            if ($primaryEmail && isset($primaryEmail['email'])) {
                $user = $userModelClass::stytchEmail($primaryEmail['email'])->first();

                if ($user) {
                    // Update the user with Stytch ID
                    $user->setStytchUserId($stytchUser['user_id']);
                    $this->updateUserFromStytch($user, $stytchUser);
                    $user->save();
                    return $user;
                }
            }
        }

        // Create new user using firstOrCreate with scope
        $userData = [
            'stytch_user_id' => $stytchUser['user_id'],
        ];

        // Add email if available
        if (isset($stytchUser['emails']) && !empty($stytchUser['emails'])) {
            $primaryEmail = $stytchUser['emails'][0] ?? null;
            if ($primaryEmail && isset($primaryEmail['email'])) {
                $userData['email'] = $primaryEmail['email'];
            }
        }

        // Add name if available
        if (isset($stytchUser['name'])) {
            if (is_array($stytchUser['name'])) {
                $firstName = $stytchUser['name']['first_name'] ?? '';
                $lastName = $stytchUser['name']['last_name'] ?? '';
                $userData['name'] = trim($firstName . ' ' . $lastName);
            } else {
                $userData['name'] = $stytchUser['name'];
            }
        }

        return $userModelClass::stytchUserId($stytchUser['user_id'])->firstOrCreate($userData);
    }

    /**
     * Handle organization data from Stytch member data.
     */
    protected function handleOrganization($user, array $stytchMember): void
    {
        $organizationModelClass = config('stytch.organization.model', 'App\Models\Organization');

        if (!class_exists($organizationModelClass)) {
            Log::warning("Organization model class {$organizationModelClass} not found, skipping organization handling");
            return;
        }

        // Check if the model uses our trait
        $usesTrait = in_array(HasStytchOrganization::class, class_uses_recursive($organizationModelClass));

        if (!$usesTrait) {
            Log::warning("Organization model {$organizationModelClass} must use the HasStytchOrganization trait");
            return;
        }

        // Try to find existing organization by Stytch organization ID
        $organization = $organizationModelClass::stytchOrganizationId($stytchMember['organization_id'])->first();

        if (!$organization) {
            // Create new organization
            $orgData = [
                'stytch_organization_id' => $stytchMember['organization_id'],
            ];

            // Add organization name if available
            if (isset($stytchMember['organization']['organization_name'])) {
                $orgData['name'] = $stytchMember['organization']['organization_name'];
            }

            $organization = $organizationModelClass::stytchOrganizationId($stytchMember['organization_id'])->firstOrCreate($orgData);
        }

        // Update user's organization relationship if the user model supports it
        if (method_exists($user, 'setStytchOrganizationId')) {
            $user->setStytchOrganizationId($stytchMember['organization_id']);
            $user->save();
        }
    }

    /**
     * Update user data from Stytch user data.
     */
    protected function updateUserFromStytch($user, array $stytchUser): void
    {
        // Update email if available and different
        if (isset($stytchUser['emails']) && !empty($stytchUser['emails'])) {
            $primaryEmail = $stytchUser['emails'][0] ?? null;
            if ($primaryEmail && isset($primaryEmail['email'])) {
                $currentEmail = $user->getEmail();
                if ($currentEmail !== $primaryEmail['email']) {
                    $user->setEmail($primaryEmail['email']);
                }
            }
        }

        // Update name if available and different
        if (isset($stytchUser['name'])) {
            $newName = '';
            if (is_array($stytchUser['name'])) {
                $firstName = $stytchUser['name']['first_name'] ?? '';
                $lastName = $stytchUser['name']['last_name'] ?? '';
                $newName = trim($firstName . ' ' . $lastName);
            } else {
                $newName = $stytchUser['name'];
            }

            if ($newName && $user->name !== $newName) {
                $user->name = $newName;
            }
        }
    }
}
