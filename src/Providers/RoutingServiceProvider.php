<?php
namespace Koselig\Providers;

use Illuminate\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Koselig\Routing\ArchiveRoute;
use Koselig\Routing\PageRoute;
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
        $app = $this->app;

        // Router methods
        Router::macro('template', function ($slug, $action) use ($app) {
            $action = $this->formatAction($action);

            $route = (new TemplateRoute($action['method'], $slug, $action))
                ->setRouter($app->make('router'))
                ->setContainer($app->make(Container::class));

            return Route::getRoutes()->add($route);
        });

        Router::macro('page', function ($slug, $action) use ($app) {
            $action = $this->formatAction($action);

            $route = (new PageRoute($action['method'], $slug, $action))
                ->setRouter($app->make('router'))
                ->setContainer($app->make(Container::class));

            return Route::getRoutes()->add($route);
        });

        Router::macro('archive', function ($postTypes = [], $action = []) use ($app) {
            if (empty($action)) {
                $action = $postTypes;
                $postTypes = [];
            }

            if (!is_array($postTypes)) {
                $postTypes = [$postTypes];
            }

            $action = $this->formatAction($action);

            $route = (new ArchiveRoute($action['method'], $postTypes, $action))
                ->setRouter($app->make('router'))
                ->setContainer($app->make(Container::class));

            return Route::getRoutes()->add($route);
        });

        Router::macro('singular', function ($types, $action) use ($app) {
            if (!is_array($types)) {
                $types = [$types];
            }

            $action = $this->formatAction($action);

            $route = (new SingularRoute($action['method'], $types, $action))
                ->setRouter($app->make('router'))
                ->setContainer($app->make(Container::class));

            return Route::getRoutes()->add($route);
        });

        // Router helpers
        Router::macro('formatAction', function ($action) {
            if (!($action instanceof $action) && (is_string($action) || (isset($action['uses'])
                    && is_string($action['uses'])))) {
                if (is_string($action)) {
                    $action = ['uses' => $action];
                }

                if (!empty($this->groupStack)) {
                    $group = end($this->groupStack);

                    $action['uses'] = isset($group['namespace']) && strpos($action['uses'], '\\') !== 0 ?
                        $group['namespace'] . '\\' . $action['uses'] : $action['uses'];
                }

                $action['controller'] = $action['uses'];
            }

            if (!is_array($action)) {
                $action = ['uses' => $action];
            }

            if (!isset($action['method'])) {
                $action['method'] = ['GET'];
            }

            return $action;
        });
    }
}
