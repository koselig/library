<?php
namespace Koselig\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for {@link Koselig\Proxy\Query} proxy. Provides access to the main query.
 *
 * @see \Koselig\Proxy\Query
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Query extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'query';
    }
}
