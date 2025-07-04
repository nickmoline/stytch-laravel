<?php

namespace LaravelStytch\Facades;

use Illuminate\Support\Facades\Facade;
use Stytch\B2B\Client as B2BClient;
use Stytch\B2C\Client as B2CClient;

/**
 * @method static B2BClient b2b()
 * @method static B2CClient b2c()
 * 
 * @see \Stytch\Stytch
 */
class Stytch extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'stytch';
    }
}
