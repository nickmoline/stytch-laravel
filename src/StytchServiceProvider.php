<?php

namespace LaravelStytch;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use LaravelStytch\Facades\Stytch as StytchFacade;
use LaravelStytch\Guards\StytchGuardFactory;
use LaravelStytch\Providers\StytchB2CUserServiceProvider;
use LaravelStytch\Providers\StytchB2BUserServiceProvider;
use Stytch\Stytch;

class StytchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/stytch.php', 'stytch');

        $this->app->singleton(Stytch::class, function ($app) {
            $config = $app['config']['stytch'];

            $stytchConfig = [
                'project_id' => $config['project_id'],
                'secret' => $config['secret'],
                'timeout' => $config['timeout'] ?? 600,
            ];
            if (isset($config['custom_base_url'])) {
                $stytchConfig['custom_base_url'] = $config['custom_base_url'];
            }

            return new Stytch($stytchConfig);
        });

        $this->app->alias(Stytch::class, 'stytch');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/stytch.php' => config_path('stytch.php'),
        ], 'stytch-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/2024_01_01_000000_add_stytch_user_id_to_users_table.php' => database_path('migrations/2024_01_01_000000_add_stytch_user_id_to_users_table.php'),
        ], 'stytch-migrations');

        // Register the guards
        $this->registerGuards();

        // Register the user providers
        $this->registerUserProviders();
    }

    /**
     * Register the Stytch guards.
     */
    protected function registerGuards(): void
    {
        Auth::extend('stytch-b2c', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);
            return StytchGuardFactory::create($name, $config, $provider);
        });

        Auth::extend('stytch-b2b', function ($app, $name, array $config) {
            $config['client_type'] = 'b2b';
            $provider = Auth::createUserProvider($config['provider'] ?? null);
            return StytchGuardFactory::create($name, $config, $provider);
        });
    }

    /**
     * Register the Stytch user providers.
     */
    protected function registerUserProviders(): void
    {
        Auth::provider('stytch-b2c', function ($app, array $config) {
            return new StytchB2CUserServiceProvider($config['model']);
        });

        Auth::provider('stytch-b2b', function ($app, array $config) {
            return new StytchB2BUserServiceProvider($config['model']);
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [Stytch::class, 'stytch', StytchFacade::class];
    }
}
