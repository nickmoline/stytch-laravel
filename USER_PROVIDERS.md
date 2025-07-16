# Stytch User Providers

This package provides custom Laravel user providers that integrate with Stytch for password authentication. These providers implement the `Illuminate\Contracts\Auth\UserProvider` contract and use the Stytch PHP package to authenticate users.

## Available Providers

### StytchB2CUserServiceProvider

For consumer (B2C) applications, this provider uses `$stytch->b2c()->passwords->authenticate()` to validate user credentials.

### StytchB2BUserServiceProvider

For business (B2B) applications, this provider uses `$stytch->b2b()->passwords->authenticate()` to validate user credentials.

## Configuration

### 1. Update your `config/auth.php`

Add the new user providers to your authentication configuration:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],

    // Stytch B2C User Provider - uses Stytch for password authentication
    'stytch-b2c-users' => [
        'driver' => 'stytch-b2c',
        'model' => App\Models\User::class,
    ],

    // Stytch B2B User Provider - uses Stytch for password authentication
    'stytch-b2b-users' => [
        'driver' => 'stytch-b2b',
        'model' => App\Models\User::class,
    ],
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    // Stytch B2C Guard - for consumer applications
    'stytch-b2c' => [
        'driver' => 'stytch-b2c',
        'provider' => 'stytch-b2c-users',
    ],

    // Stytch B2B Guard - for business applications
    'stytch-b2b' => [
        'driver' => 'stytch-b2b',
        'provider' => 'stytch-b2b-users',
    ],
],
```

### 2. Ensure your User model implements the Authenticatable contract and uses the required traits

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

**Note:** The `StytchAuthenticatable` trait provides all the required methods for Laravel's authentication system to work with Stytch users. Your model should implement the `Illuminate\Contracts\Auth\Authenticatable` contract and use the trait to satisfy the contract requirements.

## Usage

### B2C Password Authentication

```php
use Illuminate\Support\Facades\Auth;

// Attempt to authenticate with email and password
$credentials = [
    'email' => 'user@example.com',
    'password' => 'userpassword',
];

if (Auth::guard('stytch-b2c')->attempt($credentials)) {
    // User authenticated successfully
    $user = Auth::guard('stytch-b2c')->user();
    // ...
}
```

### B2B Password Authentication

```php
use Illuminate\Support\Facades\Auth;

// Attempt to authenticate with email, password, and organization_id
$credentials = [
    'email_address' => 'member@company.com',
    'password' => 'memberpassword',
    'organization_id' => 'org_1234567890',
];

if (Auth::guard('stytch-b2b')->attempt($credentials)) {
    // Member authenticated successfully
    $user = Auth::guard('stytch-b2b')->user();
    // ...
}
```

## Key Features

### Automatic User Creation/Update

Both providers will automatically:
- Find existing users by Stytch user ID or email
- Create new users if they don't exist
- Update user data from Stytch responses

### B2B Organization Support

The B2B provider includes additional features:
- Requires `organization_id` in credentials
- Automatically handles organization relationships
- Updates user organization data from Stytch member responses

### Error Handling

Both providers include comprehensive error handling:
- Logs authentication failures
- Validates required credentials
- Handles Stytch API exceptions gracefully

## Method Implementations

### retrieveById($identifier)
Retrieves a user by their Laravel ID.

### retrieveByToken($identifier, $token)
Retrieves a user by their Laravel ID and remember me token.

### updateRememberToken($user, $token)
Updates the remember me token for a user.

### retrieveByCredentials($credentials)
Retrieves a user by their credentials (email for B2C, email_address for B2B).

### validateCredentials($user, $credentials)
Validates user credentials against Stytch:
- **B2C**: Uses `$stytch->b2c()->passwords->authenticate()`
- **B2B**: Uses `$stytch->b2b()->passwords->authenticate()`

### rehashPasswordIfRequired($user, $credentials, $force)
Not applicable for Stytch authentication (passwords are managed by Stytch).

## Differences from Standard Laravel Authentication

1. **Password Management**: Passwords are validated against Stytch, not stored locally
2. **User Creation**: Users are automatically created/updated from Stytch data
3. **B2B Requirements**: B2B authentication requires an `organization_id`
4. **No Password Rehashing**: Stytch handles password security

## Security Considerations

- Passwords are never stored locally
- All password validation happens through Stytch's secure API
- User data is automatically synced from Stytch
- Remember me tokens are handled by Laravel's standard mechanisms

## Troubleshooting

### Common Issues

1. **"User model must use the HasStytchUser trait"**
   - Ensure your User model uses the `HasStytchUser` trait

2. **"User model must use the StytchAuthenticatable trait"**
   - Ensure your User model uses the `StytchAuthenticatable` trait

3. **"Stytch B2B password authentication requires organization_id"**
   - B2B authentication requires an `organization_id` in the credentials

4. **Authentication failures**
   - Check your Stytch configuration (project_id, secret)
   - Verify user exists in Stytch
   - Check Laravel logs for detailed error messages

### Debugging

Enable detailed logging in your `config/logging.php`:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single'],
        'ignore_exceptions' => false,
    ],
],
```

The providers will log authentication attempts and failures for debugging purposes. 