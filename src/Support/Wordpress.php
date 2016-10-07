<?php
namespace Koselig\Support;

/**
 * Provides various base Wordpress helper functionality in a nice
 * OO way.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Wordpress
{
    /**
     * Get the current Wordpress query.
     *
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     * @return \WP_Query
     */
    public static function query()
    {
        return $GLOBALS['wp_query'];
    }

    /**
     * Get the current page id.
     *
     * @return int
     */
    public static function id()
    {
        return get_the_ID();
    }

    /**
     * Get the slug of the template of a page.
     *
     * @param string $page
     * @return false|string
     */
    public static function templateSlug($page = null)
    {
        return get_page_template_slug($page);
    }

    /**
     * Check if the current page is a singular item (eg. a news post).
     *
     * @param array|string $types
     * @return bool
     */
    public static function singular($types = '')
    {
        return is_singular($types);
    }

    /**
     * Check if the current page is an archive page.
     *
     * @param string|array|null $types check if the archive page is for this type
     * @return bool
     */
    public static function archive($types = null)
    {
        return $types === null || empty($types) ? is_archive() : is_post_type_archive($types);
    }

    /**
     * Check if we are on a multisite, and optionally check the multisite we are on.
     *
     * @param null|int|array $id id (or ids) to check against the site, or null if you want to just check
     *                           if we are actually on a multisite
     * @return bool
     */
    public static function multisite($id = null)
    {
        if (is_array($id)) {
            foreach ($id as $i) {
                if (static::multisite($i)) {
                    return true;
                }
            }
        }

        return $id === null ? is_multisite() : ($id === static::getSiteId());
    }

    /**
     * Get the current multisite id.
     *
     * @return int
     */
    public static function getSiteId()
    {
        return get_current_blog_id();
    }

    /**
     * Get the current site that the user is currently browsing.
     *
     * @return \WP_Network
     */
    public static function site()
    {
        return get_current_site();
    }

    /**
     * Get the current logged in user. Generally, you shouldn't be using this
     * function and should instead be using Auth::user() from Laravel to get
     * the current logged in Wordpress user.
     *
     * Use of WP_User is deprecated, however this method will not be removed.
     *
     * @return \WP_User
     */
    public static function currentUser()
    {
        return wp_get_current_user();
    }
}
