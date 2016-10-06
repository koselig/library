<?php
namespace Koselig\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Input;
use Symfony\Component\Routing\Route as SymfonyRoute;

/**
 * Extend the base Laravel routing functionality to add multisite support
 * to Route::get, Route::post, etc methods.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Route extends LaravelRoute
{
    /**
     * Determine if the route matches given request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  bool $includingMethod
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        $this->compileRoute();

        foreach ($this->getValidators() as $validator) {
            if (!$includingMethod && $validator instanceof MethodValidator) {
                continue;
            }

            if (!$validator->matches($this, $request)) {
                return false;
            }
        }

        return true;
    }
}
