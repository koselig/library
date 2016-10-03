<?php
namespace Koselig\Routing;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Route;

class Routing
{
    /**
     * Register a new template route with the router.
     *
     * @param  string $slug slug to match
     * @param  \Closure|array|string|null $action
     * @return \Illuminate\Routing\Route
     */
    public function template($slug, $action)
    {
        $action = $this->formatAction($action);

        $route = (new TemplateRoute($action['method'], $slug, $action))
            ->setRouter(app('router'))
            ->setContainer(app(Container::class));

        return Route::getRoutes()->add($route);
    }

    /**
     * Register a new page route with the router.
     *
     * @param  string $slug slug to match
     * @param  \Closure|array|string|null $action
     * @return \Illuminate\Routing\Route
     */
    public function page($slug, $action)
    {
        $action = $this->formatAction($action);

        $route = (new PageRoute($action['method'], $slug, $action))
            ->setRouter(app('router'))
            ->setContainer(app(Container::class));

        return Route::getRoutes()->add($route);
    }

    /**
     * Register a new archive route with the router. Optionally supply
     * the post types you'd like to supply with this route.
     *
     * @param \Closure|string|array $postTypes
     * @param  \Closure|array|string|null $action
     * @return \Illuminate\Routing\Route
     */
    public function archive($postTypes = [], $action = [])
    {
        if (empty($action)) {
            $action = $postTypes;
            $postTypes = [];
        }

        if (!is_array($postTypes)) {
            $postTypes = [$postTypes];
        }

        $action = $this->formatAction($action);

        $route = (new ArchiveRoute($action['method'], $postTypes, $action))
            ->setRouter(app('router'))
            ->setContainer(app(Container::class));

        return Route::getRoutes()->add($route);
    }

    /**
     * Register a singular route with the router. This allows the user to
     * create pages for a single post type, ie. a news article.
     *
     * @param array|string $types post types to supply with this route
     * @param callable|string $action
     * @return mixed
     */
    public function singular($types, $action)
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        $action = $this->formatAction($action);

        $route = (new SingularRoute($action['method'], $types, $action))
            ->setRouter(app('router'))
            ->setContainer(app(Container::class));

        return Route::getRoutes()->add($route);
    }

    /**
     * Format <pre>$action</pre> in a nice way to pass to the {@link \Illuminate\Routing\RouteCollection}.
     *
     * @param $action
     * @return array|string
     */
    private function formatAction($action)
    {
        if (!($action instanceof $action) && (is_string($action) || (isset($action['uses'])
                    && is_string($action['uses'])))) {
            if (is_string($action)) {
                $action = ['uses' => $action];
            }

            if (!empty(Route::getGroupStack())) {
                $group = end(Route::getGroupStack());

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
    }
}
