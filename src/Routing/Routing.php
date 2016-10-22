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
     * @param  callable|array|string|null $action
     *
     * @return \Illuminate\Routing\Route
     */
    public function template($slug, $action)
    {
        $action = $this->formatAction($action);

        $route = (new TemplateRoute($action['method'], $slug, $action))
            ->setRouter(app('router'))
            ->setContainer(app(Container::class));

        $route = $this->applyStack($route);

        return Route::getRoutes()->add($route);
    }

    /**
     * Register a new page route with the router.
     *
     * @param  string $slug slug to match
     * @param  callable|array|string|null $action
     *
     * @return \Illuminate\Routing\Route
     */
    public function page($slug, $action)
    {
        $action = $this->formatAction($action);

        $route = (new PageRoute($action['method'], $slug, $action))
            ->setRouter(app('router'))
            ->setContainer(app(Container::class));

        $route = $this->applyStack($route);

        return Route::getRoutes()->add($route);
    }

    /**
     * Register a new archive route with the router. Optionally supply
     * the post types you'd like to supply with this route.
     *
     * @param callable|string|array $postTypes
     * @param callable|array|string|null $action
     *
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

        $route = $this->applyStack($route);

        return Route::getRoutes()->add($route);
    }

    /**
     * Register a singular route with the router. This allows the user to
     * create pages for a single post type, ie. a news article.
     *
     * @param array|string $types post types to supply with this route
     * @param callable|string $action
     *
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

        $route = $this->applyStack($route);

        return Route::getRoutes()->add($route);
    }

    /**
     * Register a author route with the router. This allows the user to
     * create pages for an author or authors. Optionally supply the authors
     * you'd like to supply using this route.
     *
     * @param callable|array|int $users authors to handle by this route
     * @param callable|array|string|null $action
     *
     * @return mixed
     */
    public function author($users, $action = [])
    {
        if (empty($action)) {
            $action = $users;
            $users = [];
        }

        if (!is_array($users)) {
            $users = [$users];
        }

        $action = $this->formatAction($action);

        $route = (new AuthorRoute($action['method'], $users, $action))
            ->setRouter(app('router'))
            ->setContainer(app(Container::class));

        $route = $this->applyStack($route);

        return Route::getRoutes()->add($route);
    }

    /**
     * Format <pre>$action</pre> in a nice way to pass to the {@link \Illuminate\Routing\RouteCollection}.
     *
     * @param $action
     *
     * @return array|string
     */
    protected function formatAction($action)
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

    /**
     * Apply group stack properties to the route and apply global "wheres" to the
     * route.
     *
     * @param $route
     *
     * @return mixed
     */
    protected function applyStack($route)
    {
        // If we have groups that need to be merged, we will merge them now after this
        // route has already been created and is ready to go. After we're done with
        // the merge we will be ready to return the route back out to the caller.
        if (Route::hasGroupStack()) {
            $action = Route::mergeWithLastGroup($route->getAction());

            $route->setAction($action);
        }

        $where = isset($route->getAction()['where']) ? $route->getAction()['where'] : [];

        $route->where(array_merge(Route::getPatterns(), $where));

        return $route;
    }
}
