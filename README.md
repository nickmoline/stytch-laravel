# Stytch Laravel Package

A Laravel package for integrating with Stytch authentication. This package provides seamless integration between Laravel and Stytch's authentication services, including automatic user creation and session management.

## Installation

```bash
composer require nickmoline/stytch-laravel
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="LaravelStytch\StytchServiceProvider" --tag="stytch-config"
```

Or publish all assets:

```bash
php artisan vendor:publish --provider="LaravelStytch\StytchServiceProvider"
```

This will create a `config/stytch.php` file with the following configuration:

**Note:** You'll also need to configure the authentication guards in your `config/auth.php` file. See the [Authentication](#authentication) section below for details.

```php
return [
    'project_id' => env('STYTCH_PROJECT_ID'),
    'secret' => env('STYTCH_SECRET'),
    'timeout' => $config['timeout'] ?? 600,
    'custom_base_url' => env('STYTCH_CUSTOM_BASE_URL'),
    
    // Cookie configuration
    'session_cookie_name' => env('STYTCH_SESSION_COOKIE_NAME', 'stytch_session'),
    'jwt_cookie_name' => env('STYTCH_JWT_COOKIE_NAME', 'stytch_session_jwt'),
    
    // User model configuration
    'user_model' => env('STYTCH_USER_MODEL', 'App\Models\User'),
    'stytch_user_id_column' => env('STYTCH_USER_ID_COLUMN', 'stytch_user_id'),
    'email_column' => env('STYTCH_EMAIL_COLUMN', 'email'),
    
    // Session configuration
    'session_timeout' => env('STYTCH_SESSION_TIMEOUT', 3600), // 1 hour
    
    // Organization configuration
    'organization' => [
        'enabled' => env('STYTCH_ORGANIZATION_ENABLED', true),
    ],
];
```

Add the following environment variables to your `.env` file:

```env
STYTCH_PROJECT_ID=your-project-id
STYTCH_SECRET=your-secret
STYTCH_TIMEOUT=600
STYTCH_CUSTOM_BASE_URL=https://test.stytch.com  # Optional, for testing
STYTCH_SESSION_COOKIE_NAME=stytch_session
STYTCH_JWT_COOKIE_NAME=stytch_session_jwt
STYTCH_USER_MODEL=App\Models\User
STYTCH_USER_ID_COLUMN=stytch_user_id
STYTCH_EMAIL_COLUMN=email
STYTCH_SESSION_TIMEOUT=3600
STYTCH_ORGANIZATION_ENABLED=true
```

## Database Migration

You'll need to add a `stytch_user_id` column to your users table. The package provides a migration that you can publish:

```bash
php artisan vendor:publish --provider="LaravelStytch\StytchServiceProvider" --tag="stytch-migrations"
```

This will create a migration file that adds a `stytch_user_id` column with the appropriate size for Stytch IDs (which follow the pattern `[model]-[env]-[uuid]`, e.g., `member-live-ebec2590-c493-49d0-9c7c-be4e692db24e`).

Alternatively, you can create the migration manually:

```bash
php artisan make:migration add_stytch_user_id_to_users_table
```

Add the following to your migration:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('stytch_user_id', 100)->nullable()->unique()->after('id');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('stytch_user_id');
    });
}
```

**Note:** The column size is set to 100 characters to accommodate Stytch's ID format which includes model type, environment, and UUID components.

## User Model Setup

Implement the Authenticatable contract and add the required traits to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LaravelStytch\Traits\HasStytchUser;
use LaravelStytch\Traits\StytchAuthenticatable;

class User extends Model implements Authenticatable
{
    use HasStytchUser, StytchAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'stytch_user_id',
    ];
}
```

### Required Traits

- **`HasStytchUser`**: Provides Stytch-specific methods and query scopes
- **`StytchAuthenticatable`**: Provides the methods required by the Laravel Authenticatable contract

The `HasStytchUser` trait provides modern Laravel 10+ attribute-based query scopes (`stytchUserId` and `stytchEmail`) for easy user lookup.

The `StytchAuthenticatable` trait satisfies the `Illuminate\Contracts\Auth\Authenticatable` contract requirements, ensuring your User model is fully compatible with Laravel's authentication system.

## Organization Model Setup (Optional)

For B2B applications, you may want to track Stytch organizations. The package provides a trait for this, but you'll need to create your own migration based on your specific requirements.

### Create Organization Model and Migration

```bash
php artisan make:model Organization -m
```

### Create Your Organization Migration

Add the `stytch_organization_id` column to your organization table migration:

```php
public function up(): void
{
    Schema::create('organizations', function (Blueprint $table) {
        $table->id();
        $table->string('stytch_organization_id', 100)->nullable()->unique();
        $table->string('name');
        // Add your other organization fields here
        $table->timestamps();
    });
}
```

### Add the HasStytchOrganization Trait

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelStytch\Traits\HasStytchOrganization;

class Organization extends Model
{
    use HasStytchOrganization;

    protected $fillable = [
        'name',
        'stytch_organization_id',
        // Add your other fillable fields
    ];
}
```

The trait provides modern Laravel 10+ attribute-based query scopes (`stytchOrganizationId` and `stytchOrganizationName`) for easy organization lookup.

## Usage

### Using the Facade

You can use the Stytch facade to access Stytch's B2B and B2C clients:

```php
use LaravelStytch\Facades\Stytch;

// B2C operations
$response = Stytch::b2c()->users()->get(['user_id' => 'user_123']);

// B2B operations
$response = Stytch::b2b()->organizations()->get(['organization_id' => 'org_123']);

// Magic links
$response = Stytch::b2c()->magicLinks()->create([
    'email' => 'user@example.com',
    'login_magic_link_url' => 'https://yourapp.com/authenticate',
]);

// OAuth
$response = Stytch::b2c()->oauth()->authenticate([
    'token' => 'oauth_token_123',
    'token_type' => 'oauth',
]);
```

### Authentication

The package provides custom authentication guards that integrate seamlessly with Laravel's authentication system and allow for multiple authentication methods. The guards use Laravel's session system to cache Stytch authentication data, reducing API latency by avoiding repeated calls to Stytch on every request.

#### Session-Based Caching

The guards automatically create Laravel sessions after successful Stytch authentication, storing:

- User ID and Stytch user ID
- Authentication timestamp
- User details (email, name)
- B2B member and organization data (when applicable)

This data is cached in Laravel's session for the duration specified by `STYTCH_SESSION_TIMEOUT` (default: 1 hour), eliminating the need to call Stytch's API on every request. When the session expires, the guard will re-authenticate with Stytch using the stored tokens.

#### Authentication Guards

The package registers custom authentication guards that you can use with Laravel's built-in `auth` middleware:

##### Available Guards

- `stytch-b2c` - Authenticates B2C users with Stytch session or JWT tokens
- `stytch-b2b` - Authenticates B2B users with Stytch session or JWT tokens (includes organization support)

##### Configuration

Add the guards to your `config/auth.php` file:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],

    // Stytch B2C Guard - for consumer applications
    'stytch-b2c' => [
        'driver' => 'stytch-b2c',
        'provider' => 'users',
    ],

    // Stytch B2B Guard - for business applications
    'stytch-b2b' => [
        'driver' => 'stytch-b2b',
        'provider' => 'users',
    ],
],
```

##### Usage with Laravel's Auth Middleware

```php
// Protect routes with specific guards
Route::middleware('auth:stytch-b2c')->group(function () {
    // B2C authenticated routes
});

Route::middleware('auth:stytch-b2b')->group(function () {
    // B2B authenticated routes
});

// Use multiple guards for endpoints that accept different authentication methods
Route::middleware('auth:web,stytch-b2c,stytch-b2b')->group(function () {
    // Routes that work with any of these authentication methods
});

// In controllers
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web,stytch-b2c');
    }
}
```

### Manual Authentication

You can also manually authenticate users:

```php
use LaravelStytch\Facades\Stytch;
use Illuminate\Support\Facades\Auth;

// Authenticate a session token
$response = Stytch::b2c()->sessions()->authenticate([
    'session_token' => $sessionToken
]);

if (isset($response['user'])) {
    $user = User::stytchUserId($response['user']['user_id'])->firstOrCreate([
        'name' => $response['user']['name'] ?? '',
        'email' => $response['user']['emails'][0]['email'] ?? '',
    ]);
    Auth::login($user);
}

// Authenticate a JWT
$response = Stytch::b2c()->sessions()->authenticateJwt([
    'session_jwt' => $jwtToken
]);

if (isset($response['user'])) {
    $user = User::stytchUserId($response['user']['user_id'])->firstOrCreate([
        'name' => $response['user']['name'] ?? '',
        'email' => $response['user']['emails'][0]['email'] ?? '',
    ]);
    Auth::login($user);
}
```

### Using Query Scopes

The trait provides modern Laravel 10+ attribute-based query scopes for finding users:

```php
// Find user by Stytch user ID
$user = User::stytchUserId('stytch_user_123')->first();

// Find user by Stytch email
$user = User::stytchEmail('user@example.com')->first();

// Chain with other Eloquent methods
$user = User::stytchUserId('stytch_user_123')->firstOrCreate([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Use in complex queries
$activeUsers = User::stytchEmail('admin@example.com')
    ->where('active', true)
    ->orderBy('created_at')
    ->get();

// Organization queries (if using B2B)
$organization = Organization::stytchOrganizationId('org-live-123456')->first();
$organizations = Organization::stytchOrganizationName('Acme Corp')->get();

### Accessing Session Data

You can access the cached Stytch session data programmatically:

```php
use Illuminate\Support\Facades\Auth;

// Get the current guard
$guard = Auth::guard('stytch-b2c');

// Access session data
$sessionData = $guard->getStytchSessionData();

if ($sessionData) {
    $stytchUserId = $sessionData['stytch_user_id'];
    $memberId = $sessionData['member_id'] ?? null;
    $organizationId = $sessionData['organization_id'] ?? null;
    $organizationName = $sessionData['organization_name'] ?? null;
}

// Clear the session (useful for logout)
$guard->clearStytchSession();
```

### Session Management

The package automatically manages Laravel sessions for Stytch authentication:

- **Session Creation**: After successful Stytch authentication, a Laravel session is created with all relevant user and organization data
- **Session Validation**: On subsequent requests, the guard checks if the session is valid and not expired
- **Session Expiry**: Sessions expire after the configured timeout (default: 1 hour)
- **Session Regeneration**: Session IDs are regenerated after authentication for security
- **Automatic Re-authentication**: When sessions expire, the guard automatically re-authenticates with Stytch using stored tokens

This approach significantly reduces API latency while maintaining security and providing a seamless user experience.

## Features

- **Custom Authentication Guards**: Seamless integration with Laravel's authentication system using custom guards
- **Multiple Guard Support**: Use Laravel's native multiple guard support for endpoints that accept different authentication methods
- **Session-Based Caching**: Automatic Laravel session creation to cache Stytch authentication data and reduce API latency
- **Automatic User Creation**: Users are automatically created in your Laravel application when they first authenticate with Stytch
- **Session Management**: Full integration with Laravel's session and authentication system with automatic session expiry and regeneration
- **Flexible Configuration**: Configurable cookie names, user model, column names, and session timeout
- **B2B and B2C Support**: Full access to both Stytch B2B and B2C APIs
- **Trait-based User Model**: Easy integration with existing user models
- **Optional Organization Support**: Trait for B2B applications that need to track Stytch organizations
- **Performance Optimized**: Reduces Stytch API calls by caching authentication data in Laravel sessions

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
