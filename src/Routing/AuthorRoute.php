<?php

namespace Koselig\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Koselig\Facades\Query;
use Koselig\Models\User;
use Koselig\Support\Wordpress;
use ReflectionFunction;

/**
 * Author page route, used when Wordpress reports
 * this page is an author page.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class AuthorRoute extends Route
{
    /**
     * Users for this author route to hook onto.
     *
     * @var array
     */
    private $users;

    /**
     * Create a new Route instance.
     *
     * @param array|string   $methods
     * @param array          $users
     * @param \Closure|array $action
     *
     * @return void
     */
    public function __construct($methods, $users, $action)
    {
        parent::__construct($methods, $users, $action);

        $this->users = $this->uri;
        $this->uri = 'author/'.(implode('/', $this->users) ?: 'all');
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
                && ($param->getClass()->isSubclassOf(User::class) || $param->getClass()->getName() === User::class)) {
                $builder = $param->getClass()->getMethod('query')->invoke(null);
                $post = $builder->find(Query::queriedObject()->ID);

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
        if (!empty($this->getAction()['domain']) && !Wordpress::multisite($this->getAction()['domain'])) {
            return false;
        }

        return Wordpress::author($this->users);
    }
}
