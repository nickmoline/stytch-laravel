<?php

/**
 * Authentication Example
 * 
 * This example demonstrates how to use the StytchGuard with Laravel's
 * authentication system, including login, logout, and session management.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use LaravelStytch\Facades\Stytch;
use App\Models\User;

// Example 1: Basic Authentication Flow
class AuthenticationController
{
    /**
     * Login with email and password using Stytch
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to authenticate using Stytch
        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();
            
            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Login a user directly (e.g., after OAuth or magic link)
     */
    public function loginUser(User $user)
    {
        Auth::login($user);
        
        return redirect()->intended('dashboard');
    }

    /**
     * Login using user ID
     */
    public function loginById($userId)
    {
        $user = Auth::loginUsingId($userId);
        
        if ($user) {
            return redirect()->intended('dashboard');
        }

        return back()->withErrors(['message' => 'User not found.']);
    }

    /**
     * Stateless authentication (no session)
     */
    public function statelessAuth(Request $request)
    {
        $credentials = $request->only(['email', 'password']);
        
        if (Auth::guard('web')->once($credentials)) {
            // User is authenticated for this request only
            return response()->json(['message' => 'Authenticated']);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }

    /**
     * Check authentication status
     */
    public function checkAuth()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $userId = Auth::id();
            
            return response()->json([
                'authenticated' => true,
                'user' => $user,
                'user_id' => $userId,
            ]);
        }

        return response()->json(['authenticated' => false]);
    }
}

// Example 2: B2B Authentication with Organization Support
class B2BAuthenticationController
{
    /**
     * Login to B2B application
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Use B2B guard
        if (Auth::guard('b2b')->attempt($credentials)) {
            $request->session()->regenerate();
            
            // Get B2B session data
            $sessionData = Auth::guard('b2b')->getStytchSessionData();
            
            return redirect()->intended('b2b-dashboard')
                ->with('organization', $sessionData['organization_name'] ?? null);
        }

        return back()->withErrors([
            'email' => 'Invalid credentials or not a member of any organization.',
        ]);
    }

    /**
     * Get current B2B session information
     */
    public function getSessionInfo()
    {
        if (Auth::guard('b2b')->check()) {
            $sessionData = Auth::guard('b2b')->getStytchSessionData();
            
            return response()->json([
                'user' => Auth::guard('b2b')->user(),
                'organization' => [
                    'id' => $sessionData['organization_id'] ?? null,
                    'name' => $sessionData['organization_name'] ?? null,
                    'slug' => $sessionData['organization_slug'] ?? null,
                ],
                'member' => [
                    'id' => $sessionData['member_id'] ?? null,
                    'email' => $sessionData['member_email'] ?? null,
                    'status' => $sessionData['member_status'] ?? null,
                ],
            ]);
        }

        return response()->json(['authenticated' => false]);
    }
}

// Example 3: Custom Authentication Logic
class CustomAuthController
{
    /**
     * Custom authentication with additional validation
     */
    public function customLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Get the user provider
        $provider = Auth::guard('web')->getProvider();
        
        // Retrieve user by credentials
        $user = $provider->retrieveByCredentials($credentials);
        
        if ($user && $provider->validateCredentials($user, $credentials)) {
            // Additional custom validation
            if ($this->isUserActive($user)) {
                Auth::login($user);
                
                // Log the login
                Log::info('User logged in', ['user_id' => $user->id]);
                
                return redirect()->intended('dashboard');
            }
        }

        return back()->withErrors([
            'email' => 'Invalid credentials or account is inactive.',
        ]);
    }

    /**
     * Check if user is active
     */
    private function isUserActive($user)
    {
        // Add your custom logic here
        return $user->active ?? true;
    }
}

// Example 4: Using Multiple Guards
class MultiGuardController
{
    /**
     * Authenticate with multiple guards
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->only(['email', 'password']);
        
        // Try B2C first
        if (Auth::guard('web')->attempt($credentials)) {
            return redirect()->intended('dashboard');
        }
        
        // Try B2B if B2C fails
        if (Auth::guard('b2b')->attempt($credentials)) {
            return redirect()->intended('b2b-dashboard');
        }
        
        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }

    /**
     * Check which type of user is authenticated
     */
    public function getUserType()
    {
        if (Auth::guard('b2b')->check()) {
            return response()->json(['type' => 'b2b', 'user' => Auth::guard('b2b')->user()]);
        }
        
        if (Auth::guard('web')->check()) {
            return response()->json(['type' => 'b2c', 'user' => Auth::guard('web')->user()]);
        }
        
        return response()->json(['type' => 'guest']);
    }
}

// Example 5: Session Management
class SessionController
{
    /**
     * Get current session information
     */
    public function getSessionInfo()
    {
        $guard = Auth::guard('web');
        
        if ($guard->check()) {
            $sessionData = $guard->getStytchSessionData();
            
            return response()->json([
                'user' => $guard->user(),
                'session' => [
                    'authenticated_at' => $sessionData['authenticated_at'] ?? null,
                    'client_type' => $sessionData['client_type'] ?? null,
                    'stytch_user_id' => $sessionData['stytch_user_id'] ?? null,
                ],
                'via_remember' => $guard->viaRemember(),
            ]);
        }
        
        return response()->json(['authenticated' => false]);
    }

    /**
     * Clear session manually
     */
    public function clearSession()
    {
        $guard = Auth::guard('web');
        $guard->clearStytchSession();
        
        return response()->json(['message' => 'Session cleared']);
    }
}

// Example 6: Middleware Usage
class ProtectedController
{
    public function __construct()
    {
        // Protect all methods with B2C authentication
        $this->middleware('auth:web');
        
        // Or protect with multiple guards
        // $this->middleware('auth:web,b2b');
    }

    public function dashboard()
    {
        $user = Auth::user();
        
        return view('dashboard', compact('user'));
    }
}

// Example 7: Route Protection
/*
// In your routes/web.php file:

// B2C routes
Route::middleware('auth:web')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});

// B2B routes
Route::middleware('auth:b2b')->group(function () {
    Route::get('/b2b/dashboard', [B2BDashboardController::class, 'index']);
    Route::get('/b2b/organization', [OrganizationController::class, 'show']);
});

// Routes that accept both B2C and B2B
Route::middleware('auth:web,b2b')->group(function () {
    Route::get('/common', [CommonController::class, 'index']);
});
*/ 