<?php
namespace Koselig\Providers;

use Illuminate\Support\ServiceProvider;

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
        $this->app->register(WordpressServiceProvider::class);
        $this->app->register(RoutingServiceProvider::class);
        $this->app->register(ConfigServiceProvider::class);
    }
}
