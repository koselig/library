<?php
namespace Koselig\Providers;

use Illuminate\Support\ServiceProvider;
use Koselig\Auth\AuthServiceProvider;
use Koselig\Hashing\HashServiceProvider;
use Koselig\Routing\RoutingServiceProvider;

/**
 * Registers all the other service providers used by this package.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class KoseligServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function register()
    {
        // Generic service providers
        $this->app->register(WordpressServiceProvider::class);
        $this->app->register(ConfigServiceProvider::class);

        // Routing service provider
        $this->app->register(RoutingServiceProvider::class);

        // Authentication service provider
        $this->app->register(AuthServiceProvider::class);

        // Hashing service provider
        $this->app->register(HashServiceProvider::class);
    }
}
