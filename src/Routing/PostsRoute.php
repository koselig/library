<?php
namespace Koselig\Routing;

use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Illuminate\Http\Request;
use Koselig\Http\Request as KoseligRequest;
use Koselig\Models\Post;
use Koselig\Support\Wordpress;
use ReflectionFunction;

/**
 * Posts route, this route is matched when the user visits the 'posts page' set in
 * the Reading Settings.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class PostsRoute extends Route
{
    /**
     * Create a new Route instance.
     *
     * @param  array|string $methods
     * @param  \Closure|array $action
     *
     * @return void
     */
    public function __construct($methods, $action)
    {
        if (!get_option('page_for_posts')) {
            trigger_error('Attempted to define a posts route when the posts page isn\'t set. Set it in the Reading Settings in Wordpress.', E_USER_NOTICE);
        }

        parent::__construct($methods, wp_make_link_relative(get_permalink(get_option('page_for_posts'))) ?: '/posts', $action);
    }

    /**
     * Determine if the route matches given request.
     *
     * @param  Request $request
     * @param  bool $includingMethod
     *
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        return query()->isPostsPage ?? false;
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
