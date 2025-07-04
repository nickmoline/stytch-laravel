<?php

namespace LaravelStytch\Guards;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StytchGuardFactory
{
    /**
     * Create a new Stytch guard instance.
     */
    public static function create(string $name, array $config, UserProvider $provider): StytchGuard
    {
        $request = app(Request::class);
        $clientType = $config['client_type'] ?? 'b2c';

        return new StytchGuard($name, $provider, $request, $clientType);
    }
} 