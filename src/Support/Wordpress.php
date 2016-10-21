<?php
namespace Koselig\Support;

use Koselig\Facades\Query;
use Koselig\Models\User;

/**
 * Provides various base Wordpress helper functionality in a nice
 * OO way.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Wordpress
{
    /**
     * Get the page id.
     *
     * @return int
     */
    public static function id()
    {
        // can't use facades to access properties unfortunately!
        return app('query')->post->ID;
    }

    /**
     * Check if the current page is a singular item (eg. a news post).
     *
     * @param array|string $types
     * @return bool
     */
    public static function singular($types = '')
    {
        return Query::singular($types);
    }

    /**
     * Check if the current page is an archive page.
     *
     * @param string|array|null $types check if the archive page is for this type
     * @return bool
     */
    public static function archive($types = null)
    {
        return $types === null || empty($types) ? Query::archive() : Query::postTypeArchive($types);
    }

    /**
     * Check if the current page is an author page.
     *
     * @param int|array|User $users
     * @return bool
     */
    public static function author($users = [])
    {
        if (!is_array($users)) {
            $users = [$users];
        }

        foreach ($users as $key => $user) {
            if ($user instanceof User) {
                $users[$key] = $user->ID;
            }
        }

        return Query::author($users);
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
     * @deprecated use <code>Auth::user()</code> instead.
     * @return \WP_User
     */
    public static function currentUser()
    {
        return wp_get_current_user();
    }
}
