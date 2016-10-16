<?php
namespace Koselig\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for {@link Koselig\Models\Post}. Provides access to the current post in "The Loop"
 *
 * @see \Koselig\Models\Post
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Loop extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        // we don't want resolveFacadeInstance to cache this value since it will always change, so
        // we have to pass an object back.
        return app('loop');
    }
}
