<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stytch Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Stytch authentication service.
    | You can find your project ID and secret in the Stytch dashboard.
    |
    */

    'project_id' => env('STYTCH_PROJECT_ID'),

    'secret' => env('STYTCH_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | The name of the cookie to use for the Stytch session.
    |
    */
    'session_cookie_name' => env('STYTCH_SESSION_COOKIE_NAME', 'stytch_session'),

    /*
    |--------------------------------------------------------------------------
    | JWT Cookie Name
    |--------------------------------------------------------------------------
    |
    | The name of the cookie to use for the Stytch JWT.
    |
    */
    'jwt_cookie_name' => env('STYTCH_JWT_COOKIE_NAME', 'stytch_session_jwt'),

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The model to use for the Stytch user.
    |
    */
    'user_model' => env('STYTCH_USER_MODEL', 'User'),

    /*
    |--------------------------------------------------------------------------
    | Stytch User ID Column
    |--------------------------------------------------------------------------
    |
    | The column to use for the Stytch user ID.
    |
    */
    'stytch_user_id_column' => env('STYTCH_USER_ID_COLUMN', 'stytch_user_id'),

    /*
    |--------------------------------------------------------------------------
    | Email Column
    |--------------------------------------------------------------------------
    |
    | The column to use for the email.
    */
    'email_column' => env('STYTCH_EMAIL_COLUMN', 'email'),

    /*
    |--------------------------------------------------------------------------
    | Organization Model
    |--------------------------------------------------------------------------
    |
    | The model to use for the Stytch organization (optional).
    |
    */
    'organization_model' => env('STYTCH_ORGANIZATION_MODEL', 'App\Models\Organization'),

    /*
    |--------------------------------------------------------------------------
    | Stytch Organization ID Column
    |--------------------------------------------------------------------------
    |
    | The column to use for the Stytch organization ID.
    |
    */
    'stytch_organization_id_column' => env('STYTCH_ORGANIZATION_ID_COLUMN', 'stytch_organization_id'),

    /*
    |--------------------------------------------------------------------------
    | Stytch Organization Name Column
    |--------------------------------------------------------------------------
    |
    | The column to use for the Stytch organization name.
    |
    */
    'stytch_organization_name_column' => env('STYTCH_ORGANIZATION_NAME_COLUMN', 'name'),

    /*
    |--------------------------------------------------------------------------
    | Custom API URL
    |--------------------------------------------------------------------------
    |
    | Custom Stytch API URL, only set if you are using a custom Stytch 
    | API Domain.
    |
    */
    'custom_base_url' => env('STYTCH_API_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout for the Stytch API requests. Defaults to 10 minutes.
    |
    */
    'timeout' => env('STYTCH_TIMEOUT', 600),

    /*
    |--------------------------------------------------------------------------
    | Default Authentication Method
    |--------------------------------------------------------------------------
    |
    | The default authentication method to use when not specified.
    | Options: 'b2b', 'b2c'
    |
    */
    'default_auth_method' => env('STYTCH_DEFAULT_AUTH_METHOD', 'b2c'),

    /*
    |--------------------------------------------------------------------------
    | Session Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout for the Stytch session in seconds. After this time,
    | the user will need to re-authenticate with Stytch. Defaults to 1 hour.
    |
    */
    'session_timeout' => env('STYTCH_SESSION_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | Organization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for B2B organization handling.
    |
    */
    'organization' => [
        'enabled' => env('STYTCH_ORGANIZATION_ENABLED', true),
    ],
];
