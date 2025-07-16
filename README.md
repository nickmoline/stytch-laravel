# Stytch Laravel Package

A Laravel package for integrating Stytch authentication into your Laravel application. This package provides seamless integration with Stytch's B2C and B2B authentication services.

## Features

- **B2C Authentication**: Full support for Stytch's B2C authentication flow
- **B2B Authentication**: Complete B2B authentication with organization support
- **Custom User Providers**: `StytchB2CUserServiceProvider` and `StytchB2BUserServiceProvider` for password authentication
- **Custom Guards**: `StytchGuard` implementing Laravel's `StatefulGuard` interface
- **Traits**: `HasStytchUser`, `HasStytchOrganization`, and `StytchAuthenticatable` traits
- **Session Management**: Automatic session handling with Stytch tokens
- **Organization Support**: Built-in organization management for B2B applications

## Installation

1. Install the package via Composer:

```bash
composer require nickmoline/stytch-laravel
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --provider="LaravelStytch\StytchServiceProvider"
```

3. Add your Stytch credentials to your `.env` file:

```env
STYTCH_PROJECT_ID=your-project-id
STYTCH_SECRET=your-secret-key
STYTCH_ENV=test  # or 'live'
```

4. Run the migration to add the Stytch user ID column:

```bash
php artisan migrate
```

## Configuration

### Basic Configuration

The package configuration is located in `config/stytch.php`. Here are the key options:

```php
return [
    'project_id' => env('STYTCH_PROJECT_ID'),
    'secret' => env('STYTCH_SECRET'),
    'env' => env('STYTCH_ENV', 'test'),
    
    'user_model' => 'App\Models\User',
    'stytch_user_id_column' => 'stytch_user_id',
    'email_column' => 'email',
    
    'session_cookie_name' => 'stytch_session',
    'jwt_cookie_name' => 'stytch_session_jwt',
    'session_timeout' => 3600, // 1 hour
    
    'organization' => [
        'enabled' => true,
        'model' => 'App\Models\Organization',
        'stytch_organization_id_column' => 'stytch_organization_id',
    ],
];
```

### Authentication Configuration

Update your `config/auth.php` to use the Stytch guards and providers:

```php
'guards' => [
    'web' => [
        'driver' => 'stytch-b2c',
        'provider' => 'stytch-b2c',
    ],
    'b2b' => [
        'driver' => 'stytch-b2b',
        'provider' => 'stytch-b2b',
    ],
],

'providers' => [
    'stytch-b2c' => [
        'driver' => 'stytch-b2c',
        'model' => 'App\Models\User',
    ],
    'stytch-b2b' => [
        'driver' => 'stytch-b2b',
        'model' => 'App\Models\User',
    ],
],
```

## Usage

### User Models

Your user model should implement the `Authenticatable` contract and use the provided traits:

```php
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use LaravelStytch\Traits\HasStytchUser;
use LaravelStytch\Traits\StytchAuthenticatable;

class User implements Authenticatable
{
    use HasStytchUser, StytchAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'stytch_user_id',
    ];
}
```

### Authentication Methods

The `StytchGuard` implements Laravel's `StatefulGuard` interface, providing all standard authentication methods:

#### Login

```php
// Login with credentials
Auth::attempt(['email' => $email, 'password' => $password]);

// Login a user directly
Auth::login($user);

// Login using user ID
Auth::loginUsingId($userId);

// Login without session (stateless)
Auth::once(['email' => $email, 'password' => $password]);
```

#### Logout

```php
// Logout the current user
Auth::logout();
```

#### Check Authentication

```php
// Check if user is authenticated
if (Auth::check()) {
    // User is logged in
}

// Get current user
$user = Auth::user();

// Get user ID
$userId = Auth::id();
```

### B2B Organization Support

For B2B applications, you can also use the `HasStytchOrganization` trait:

```php
<?php

namespace App\Models;

use LaravelStytch\Traits\HasStytchOrganization;

class Organization extends Model
{
    use HasStytchOrganization;

    protected $fillable = [
        'name',
        'stytch_organization_id',
    ];
}
```

### Session Management

The guard automatically handles Stytch session tokens and creates Laravel sessions. You can access Stytch session data:

```php
// Get Stytch session data
$sessionData = Auth::guard('b2b')->getStytchSessionData();

// Clear Stytch session
Auth::guard('b2b')->clearStytchSession();
```

## Advanced Usage

### Custom Authentication Logic

You can extend the guard or create custom authentication logic:

```php
// Custom authentication with additional logic
$user = Auth::guard('b2c')->getProvider()->retrieveByCredentials([
    'email' => $email,
    'password' => $password,
]);

if ($user && Auth::guard('b2c')->getProvider()->validateCredentials($user, $credentials)) {
    Auth::login($user);
    // Custom logic here
}
```

### Multiple Guards

You can use different guards for different parts of your application:

```php
// B2C authentication
Auth::guard('web')->attempt($credentials);

// B2B authentication
Auth::guard('b2b')->attempt($credentials);
```

## Troubleshooting

### Common Issues

1. **"Call to undefined method login()"**: Make sure you're using the latest version of the package that implements `StatefulGuard`.

2. **User not found**: Ensure your user model uses the `HasStytchUser` trait and has the correct Stytch user ID.

3. **Session issues**: Check that your session configuration is correct and that cookies are being set properly.

### Debug Mode

Enable debug mode in your `.env` file to see detailed error messages:

```env
STYTCH_DEBUG=true
```

## Security Considerations

- Always use HTTPS in production
- Regularly rotate your Stytch API keys
- Implement proper session timeout
- Use CSRF protection for all forms
- Validate and sanitize all user inputs

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
