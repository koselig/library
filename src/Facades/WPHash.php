<?php

namespace Koselig\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for {@link \Koselig\Hashing\WordpressHasher}.
 *
 * @see \Koselig\Hashing\WordpressHasher
 * @mixin \Koselig\Hashing\WordpressHasher
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class WPHash extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'wphash';
    }
}
