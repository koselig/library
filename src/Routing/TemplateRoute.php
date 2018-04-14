<?php
namespace Koselig\Routing;

use Illuminate\Routing\Route;
use Koselig\Http\Request;
use Koselig\Models\Post;
use Koselig\Support\Wordpress;
use ReflectionFunction;

/**
 * Template route, this route is matched then the Wordpress
 * template set in the backend equals the slug of this route.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class TemplateRoute extends Route
{
    /**
     * Create a new Route instance.
     *
     * @param  array|string $methods
     * @param  array $types
     * @param  \Closure|array $action
     *
     * @return void
     */
    public function __construct($methods, $types, $action)
    {
        parent::__construct($methods, $types, $action);

        $this->uri = 'template/' . parent::uri();
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
        $post = $request->post();

        if (!$post) {
            // the page we are on either isn't in the CMS or doesn't have a template.
            return false;
        }

        $slug = $post->getMeta('_wp_page_template');

        if (!empty($this->getAction()['domain']) && !Wordpress::multisite($this->getAction()['domain'])) {
            return false;
        }

        return $this->uri === 'template/' . $slug;
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
