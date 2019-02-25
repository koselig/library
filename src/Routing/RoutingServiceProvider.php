<?php
namespace Koselig\Routing;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Koselig\Http\Request;

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
        $this->app->alias('request', Request::class);

        $routing = new Routing;

        // Router methods
        Router::macro('template', [$routing, 'template']);
        Router::macro('page', [$routing, 'page']);
        Router::macro('archive', [$routing, 'archive']);
        Router::macro('singular', [$routing, 'singular']);
        Router::macro('author', [$routing, 'author']);
        Router::macro('category', [$routing, 'category']);
        Router::macro('posts', [$routing, 'posts']);
    }
}
