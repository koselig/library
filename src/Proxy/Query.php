<?php
namespace Koselig\Proxy;

use Illuminate\Support\Str;
use WP_Query;

/**
 * Proxies the {@link \WP_Query} class from Wordpress for a more elegant syntax.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Query
{
    /**
     * Current query.
     *
     * @var WP_Query
     */
    private $query;

    public static function instance(WP_Query $query)
    {
        $instance = new static;
        $instance->query = $query;
        return $instance;
    }

    /**
     * Get a property from {@link WP_Query}
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->query->{Str::snake($name)};
    }

    /**
     * Pass a call to this function to {@link WP_Query}
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $name = Str::snake($name);
        $name = str_replace('has', 'have', $name);

        if (!method_exists($this->query, $name)) {
            // try and find the method that was attempted to be called. Makes for a lot nicer code when reading over
            // it.
            if (method_exists($this->query, 'the_' . $name)) {
                $name = 'the_' . $name;
            } elseif (method_exists($this->query, 'is_' . $name)) {
                $name = 'is_' . $name;
            } elseif (method_exists($this->query, 'get_' . $name)) {
                $name = 'get_' . $name;
            }
        }

        return $this->query->{$name}(...$arguments);
    }
}
