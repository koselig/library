<?php
namespace Koselig\Routing;

use Illuminate\Container\Container;
use Illuminate\Routing\Router;
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
     * Register a new category route with the router. Optionally supply
     * the categories you'd like to supply with this route.
     *
     * @param callable|string|array $categories
     * @param callable|array|string|null $action
     *
     * @return \Illuminate\Routing\Route
     */
    public function category($categories = [], $action = [])
    {
        if (empty($action)) {
            $action = $categories;
            $categories = [];
        }

        if (!is_array($categories)) {
            $categories = [$categories];
        }

        $action = $this->formatAction($action);

        $route = (new CategoryRoute($action['method'], $categories, $action))
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
     * Determine if the router currently has a group stack.
     *
     * @return bool
     */
    public function hasGroupStack()
    {
        return !empty($this->groupStack);
    }

    /**
     * Determine if the action is routing to a controller.
     *
     * @param  array  $action
     *
     * @return bool
     */
    protected function actionReferencesController($action)
    {
        if (!$action instanceof \Closure) {
            return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
        }

        return false;
    }

    /**
     * Prepend the last group namespace onto the use clause.
     *
     * @param  string  $class
     *
     * @return string
     */
    protected function prependGroupNamespace($class)
    {
        $group = end($this->groupStack);

        return isset($group['namespace']) && strpos($class, '\\') !== 0
            ? $group['namespace'] . '\\' . $class : $class;
    }

    /**
     * Add a controller based route action to the action array.
     *
     * @param  array|string  $action
     *
     * @return array
     */
    protected function convertToControllerAction($action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }
        // Here we'll merge any group "uses" statement if necessary so that the action
        // has the proper clause for this property. Then we can simply set the name
        // of the controller on the action and return the action array for usage.
        if (!empty($this->groupStack)) {
            $action['uses'] = $this->prependGroupNamespace($action['uses']);
        }
        // Here we will set this controller name on the action array just so we always
        // have a copy of it for reference if we need it. This can be used while we
        // search for a controller name or do some other type of fetch operation.
        $action['controller'] = $action['uses'];

        return $action;
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
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
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
     * Add the necessary where clauses to the route based on its initial registration.
     *
     * @param  \Illuminate\Routing\Route  $route
     *
     * @return \Illuminate\Routing\Route
     */
    protected function addWhereClausesToRoute($route)
    {
        $route->where(array_merge(
            Route::getPatterns(), $route->getAction()['where'] ?? []
        ));

        return $route;
    }

    /**
     * Merge the group stack with the controller action.
     *
     * @param  \Illuminate\Routing\Route  $route
     *
     * @return void
     */
    protected function mergeGroupAttributesIntoRoute($route)
    {
        $route->setAction($this->mergeWithLastGroup($route->getAction()));
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
        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }
        $this->addWhereClausesToRoute($route);

        return $route;
    }
}
