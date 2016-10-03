<?php
namespace Koselig\Providers;

use Illuminate\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Koselig\Routing\ArchiveRoute;
use Koselig\Routing\PageRoute;
use Koselig\Routing\Routing;
use Koselig\Routing\SingularRoute;
use Koselig\Routing\TemplateRoute;

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
