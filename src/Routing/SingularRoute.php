<?php

namespace Koselig\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Koselig\Models\Post;
use Koselig\Support\Wordpress;
use ReflectionFunction;

/**
 * Singular route, this route is matched when the user
 * hits a page that is owned by a certain Wordpress post type.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class SingularRoute extends Route
{
    /**
     * Entry types this route should hook onto.
     *
     * @var array
     */
    private $types;

    /**
     * Create a new Route instance.
     *
     * @param array|string   $methods
     * @param array          $types
     * @param \Closure|array $action
     *
     * @return void
     */
    public function __construct($methods, $types, $action)
    {
        parent::__construct($methods, $types, $action);

        $this->types = $this->uri;
        $this->uri = 'singular/'.implode('/', $this->types);
    }

    /**
     * Run the route action and return the response.
     *
     * @return mixed
     */
    protected function runCallable()
    {
        // bind the current post to the parameters of the function
        $function = new ReflectionFunction($this->action['uses']);
        $params = $function->getParameters();

        foreach ($params as $param) {
            if ($param->getClass()
                && ($param->getClass()->isSubclassOf(Post::class) || $param->getClass()->getName() === Post::class)) {
                $builder = $param->getClass()->getMethod('query')->invoke(null);
                $post = $builder->find(Wordpress::id());

                $this->setParameter($param->getName(), $post);
            }
        }

        return parent::runCallable();
    }

    /**
     * Determine if the route matches given request.
     *
     * @param \Illuminate\Http\Request $request
     * @param bool                     $includingMethod
     *
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        if (!Wordpress::id()) {
            // this isn't a wordpress-controlled page
            return false;
        }

        if (!empty($this->getAction()['domain']) && !Wordpress::multisite($this->getAction()['domain'])) {
            return false;
        }

        return Wordpress::singular($this->types);
    }
}
