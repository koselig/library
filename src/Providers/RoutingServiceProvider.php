<?php
namespace Koselig\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Koselig\Routing\Routing;

/**
 * Provides routing methods for Wordpress-related routes.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function register()
    {
        $routing = new Routing;

        // Router methods
        Router::macro('template', [$routing, 'template']);
        Router::macro('page', [$routing, 'page']);
        Router::macro('archive', [$routing, 'archive']);
        Router::macro('singular', [$routing, 'singular']);
    }
}
