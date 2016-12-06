<?php

use Koselig\Models\Meta;
use Koselig\Models\Post;
use Koselig\Support\RecursiveMenuIterator;

if (!function_exists('query')) {
    /**
     * Get the main query or convert a {@link WP_Query} to a {@link \Koselig\Proxy\Query} proxy instance.
     *
     * @param WP_Query|null $query
     *
     * @return \Koselig\Proxy\Query
     */
    function query(\WP_Query $query = null)
    {
        return ($query === null) ? app('query') : Query::instance($query);
    }
}

if (!function_exists('post')) {
    /**
     * Get the current post in The Loop, or convert a {@link \WP_Post} instance to a Koselig
     * post.
     *
     * @param WP_Post|null $post
     *
     * @return Post|null
     */
    function post(WP_Post $post = null)
    {
        return ($post === null) ? app('loop') : Post::find($post->ID);
    }
}

if (!function_exists('meta')) {
    /**
     * Grab a meta item from the database for the current page.
     *
     * @param string|null $name name of the field to get (or null for all)
     *
     * @return mixed
     */
    function meta($name = null)
    {
        return Meta::get($name);
    }
}

if (!function_exists('field')) {
    /**
     * Grab an ACF field from the database for the current page.
     *
     * @see Meta::acf()
     *
     * @param string|null $name name of the field to get (or null for all)
     *
     * @return mixed
     */
    function field($name = null)
    {
        return Meta::acf($name);
    }
}

if (!function_exists('menu')) {
    /**
     * Get a {@link RecursiveIteratorIterator} for a Wordpress menu.
     *
     * @param string $name name of the menu to get
     * @param int $depth how far to recurse down the nodes
     * @param int $mode flags to pass to the {@link RecursiveIteratorIterator}
     * @return RecursiveIteratorIterator
     */
    function menu($name, $depth = -1, $mode = RecursiveIteratorIterator::SELF_FIRST)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveMenuIterator($name), $mode);
        $iterator->setMaxDepth($depth);
        return $iterator;
    }
}
