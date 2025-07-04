<?php

/**
 * Example: Session-Based Authentication with Stytch Laravel Package
 * 
 * This example demonstrates how the package automatically manages Laravel sessions
 * to reduce API latency and improve performance.
 */

use LaravelStytch\Facades\Stytch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Example 1: Accessing session data after authentication
Route::get('/dashboard', function () {
    // The guard automatically creates a Laravel session after successful Stytch authentication
    // You can access this data without making additional API calls
    
    $guard = Auth::guard('stytch-b2c');
    $sessionData = $guard->getStytchSessionData();
    
    if ($sessionData) {
        echo "User ID: " . $sessionData['user_id'] . "\n";
        echo "Stytch User ID: " . $sessionData['stytch_user_id'] . "\n";
        echo "Email: " . $sessionData['email'] . "\n";
        echo "Name: " . $sessionData['name'] . "\n";
        echo "Client Type: " . $sessionData['client_type'] . "\n";
        echo "Authenticated At: " . date('Y-m-d H:i:s', $sessionData['authenticated_at']) . "\n";
        
        // For B2B applications, you'll also have organization data
        if (isset($sessionData['member_id'])) {
            echo "Member ID: " . $sessionData['member_id'] . "\n";
            echo "Organization ID: " . $sessionData['organization_id'] . "\n";
            echo "Organization Name: " . $sessionData['organization_name'] . "\n";
            echo "Member Email: " . $sessionData['member_email'] . "\n";
            echo "Member Status: " . $sessionData['member_status'] . "\n";
        }
    }
    
    return view('dashboard', compact('sessionData'));
})->middleware('auth:stytch-b2c');

// Example 2: Manual logout with session clearing
Route::post('/logout', function () {
    $guard = Auth::guard('stytch-b2c');
    
    // Clear the Stytch session data
    $guard->clearStytchSession();
    
    // Logout the user
    Auth::logout();
    
    return redirect('/login');
});

// Example 3: Checking session validity
Route::get('/api/user', function () {
    $guard = Auth::guard('stytch-b2c');
    
    // Check if we have a valid session
    if ($guard->hasValidStytchSession()) {
        $sessionData = $guard->getStytchSessionData();
        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $sessionData['user_id'],
                'stytch_user_id' => $sessionData['stytch_user_id'],
                'email' => $sessionData['email'],
                'name' => $sessionData['name'],
            ],
            'session_expires_at' => date('Y-m-d H:i:s', $sessionData['authenticated_at'] + config('stytch.session_timeout')),
        ]);
    }
    
    return response()->json(['authenticated' => false]);
});

// Example 4: B2B organization context
Route::get('/organization/dashboard', function () {
    $guard = Auth::guard('stytch-b2b');
    $sessionData = $guard->getStytchSessionData();
    
    if ($sessionData && isset($sessionData['organization_id'])) {
        return view('organization.dashboard', [
            'organization' => [
                'id' => $sessionData['organization_id'],
                'name' => $sessionData['organization_name'],
                'slug' => $sessionData['organization_slug'],
            ],
            'member' => [
                'id' => $sessionData['member_id'],
                'email' => $sessionData['member_email'],
                'status' => $sessionData['member_status'],
            ],
        ]);
    }
    
    return redirect('/login');
})->middleware('auth:stytch-b2b');

// Example 5: Multiple authentication methods
Route::get('/api/protected', function () {
    // This route accepts multiple authentication methods
    // Laravel will try each guard in order until one succeeds
    
    $user = Auth::user();
    
    return response()->json([
        'message' => 'Protected resource accessed successfully',
        'user' => $user,
        'auth_method' => Auth::getDefaultDriver(),
    ]);
})->middleware('auth:web,stytch-b2c,stytch-b2b');

/**
 * Configuration Example
 * 
 * Add these to your .env file:
 * 
 * STYTCH_SESSION_TIMEOUT=3600          # Session timeout in seconds (1 hour)
 * STYTCH_ORGANIZATION_ENABLED=true     # Enable B2B organization support
 * 
 * The session timeout determines how long the cached authentication data
 * remains valid before requiring re-authentication with Stytch.
 */

/**
 * Performance Benefits
 * 
 * 1. Reduced API Calls: Instead of calling Stytch's API on every request,
 *    the guard uses cached session data for subsequent requests.
 * 
 * 2. Faster Response Times: No network latency for authentication checks
 *    on cached sessions.
 * 
 * 3. Automatic Re-authentication: When sessions expire, the guard
 *    automatically re-authenticates with Stytch using stored tokens.
 * 
 * 4. Security: Session IDs are regenerated after authentication,
 *    and sessions have configurable timeouts.
 */ 