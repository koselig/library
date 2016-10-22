<?php
namespace Koselig\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Koselig\Models\Post;
use Koselig\Support\Wordpress;
use ReflectionFunction;

/**
 * Single page route, this route is matched when the
 * Wordpress page id is equal to the slug of this route.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class PageRoute extends Route
{
    /**
     * Create a new Route instance.
     *
     * @param  array|string $methods
     * @param  array $users
     * @param  \Closure|array $action
     *
     * @return void
     */
    public function __construct($methods, $users, $action)
    {
        parent::__construct($methods, $users, $action);
        $this->uri = 'page/' . $this->uri();
    }

    /**
     * Determine if the route matches given request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  bool $includingMethod
     *
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        $id = Wordpress::id();

        if (! $id) {
            // we're not on a Wordpress page
            return false;
        }

        if (! empty($this->getAction()['domain']) && ! Wordpress::multisite($this->getAction()['domain'])) {
            return false;
        }

        return $this->uri === $id;
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
}
