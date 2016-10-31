<?php
use Koselig\Models\Meta;

if (!function_exists('meta')) {
    /**
     * Grab a meta item from the database for the current page
     *
     * @param string|null $name name of the field to get (or null for all)
     * @return mixed
     */
    function meta($name = null)
    {
        return Meta::get($name);
    }
}

if (!function_exists('acf')) {
    /**
     * Grab an ACF field from the database for the current page.
     *
     * @see Meta::acf()
     *
     * @param string|null $name name of the field to get (or null for all)
     * @return mixed
     */
    function acf($name = null)
    {
        return Meta::acf($name);
    }
}
