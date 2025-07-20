<?php

/**
 * Example of how to use the Stytch middleware in Laravel routes.
 * 
 * This example demonstrates how to apply the Stytch authentication middleware
 * to routes that require authentication via Stytch.
 */

// In your routes/web.php or routes/api.php file:

// Apply to individual routes
Route::get('/dashboard', function () {
    // User will be automatically authenticated via Stytch if they have valid cookies
    return view('dashboard');
})->middleware('stytch.auth');

// Apply to route groups
Route::middleware(['stytch.auth'])->group(function () {
    Route::get('/profile', function () {
        // User is guaranteed to be authenticated here
        $user = auth()->user();
        return view('profile', compact('user'));
    });
    
    Route::get('/settings', function () {
        // User is guaranteed to be authenticated here
        return view('settings');
    });
});

// Apply to controllers
Route::controller(ProfileController::class)
    ->middleware('stytch.auth')
    ->group(function () {
        Route::get('/profile', 'show');
        Route::put('/profile', 'update');
    });

/**
 * Example of how the middleware works:
 * 
 * 1. First, it checks if the user is already logged in via Laravel sessions
 * 2. If not logged in, it checks for Stytch cookies (session_token or session_jwt)
 * 3. If cookies are found, it authenticates with Stytch using the appropriate method (B2B or B2C)
 * 4. If authentication succeeds, it finds or creates the user in your database
 * 5. It logs the user in using Laravel's Auth::login() method
 * 6. The user object is then available to all subsequent middleware and route handlers
 * 
 * Configuration:
 * 
 * Make sure your .env file has the necessary Stytch configuration:
 * 
 * STYTCH_PROJECT_ID=your_project_id
 * STYTCH_SECRET=your_secret
 * STYTCH_DEFAULT_AUTH_METHOD=b2c  # or b2b
 * STYTCH_SESSION_COOKIE_NAME=stytch_session
 * STYTCH_JWT_COOKIE_NAME=stytch_session_jwt
 * STYTCH_USER_MODEL=App\Models\User
 * 
 * Your User model should use the required traits and implement the contract:
 * 
 * use LaravelStytch\Contracts\StytchUserContract;
 * use LaravelStytch\Traits\HasStytchUser;
 * use LaravelStytch\Traits\StytchAuthenticatable;
 * 
 * class User extends Model implements Authenticatable, StytchUserContract
 * {
 *     use HasStytchUser, StytchAuthenticatable;
 *     
 *     // Override contract methods to map to your actual field names
     // Email handling is already provided by the HasStytchUser trait
 *     public function updateStytchEmail(string $email): void
 *     {
 *         $this->email = $email;
 *     }
 *     
 *     public function updateStytchName(string $name): void
 *     {
 *         $this->name = $name;
 *     }
 *     
 *     public function getStytchName(): ?string
 *     {
 *         return $this->name;
 *     }
 * }
 */ 