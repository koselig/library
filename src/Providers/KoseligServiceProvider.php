<?php
namespace Koselig\Providers;

use Illuminate\Support\ServiceProvider;
use Koselig\Auth\AuthServiceProvider;
use Koselig\Hashing\HashServiceProvider;
use Koselig\Http\Request;
use Koselig\Mail\WordpressMailServiceProvider;
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
        // override request() method to provide our Request
        $this->app->alias('request', Request::class);

        // Generic service providers
        $this->app->register(WordpressMailServiceProvider::class);
        $this->app->register(WordpressServiceProvider::class);
        $this->app->register(ConfigServiceProvider::class);
        $this->app->register(QueryServiceProvider::class);

        // Blade service provider
        $this->app->register(WordpressTemplatingServiceProvider::class);

        // Routing service provider
        $this->app->register(RoutingServiceProvider::class);

        // Authentication service provider
        $this->app->register(AuthServiceProvider::class);

        // Hashing service provider
        $this->app->register(HashServiceProvider::class);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../public/wp-config.php' => public_path()
        ], 'public');
    }
}
