<?php

namespace Koselig\Admin;

use Closure;
use Illuminate\Support\Facades\Route;

/**
 * Various helper methods to allow for extension of the Wordpress administration panel.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Page
{
    /**
     * Add a top-level menu page. Action takes the same parameters as any Route method, for example a string in the format
     * method@class or a callback. Dependencies are automatically injected by this function.
     *
     * @param string $pageTitle  The text to be displayed in the title tags of the page when the menu is selected.
     * @param string $menuTitle  The text to be used for the menu.
     * @param string $capability The capability required for this menu to be displayed to the user.
     * @param string $slug       The slug name to refer to this menu by (should be unique for this menu).
     * @param mixed  $action     The function to be called to output the content for this page.
     * @param string $iconUrl    The URL to the icon to be used for this menu.
     * @param int    $position   The position in the menu order this one should appear.
     *
     * @return string The resulting page's hook_suffix.
     */
    public static function addPage($pageTitle, $menuTitle, $capability, $slug, $action, $iconUrl = '', $position = null)
    {
        return add_menu_page($pageTitle, $menuTitle, $capability, $slug, self::wrap($action), $iconUrl, $position);
    }

    /**
     * Add a submenu page. Action takes the same parameters as any Route method, for example a string in the format
     * method@class or a callback. Dependencies are automatically injected by this function.
     *
     * @param string $parent       The slug name for the parent menu (or the file name of a standard WordPress admin page).
     * @param string $pageTitle    The text to be displayed in the title tags of the page when the menu is selected.
     * @param string $menuTitle    The text to be used for the menu.
     * @param string $capabilities The capability required for this menu to be displayed to the user.
     * @param string $slug         The slug name to refer to this menu by (should be unique for this menu).
     * @param mixed  $action       The function to be called to output the content for this page.
     *
     * @return false|string The resulting page's hook_suffix, or false if the user does not have the capability required
     */
    public static function addSubpage($parent, $pageTitle, $menuTitle, $capabilities, $slug, $action)
    {
        return add_submenu_page($parent, $pageTitle, $menuTitle, $capabilities, $slug, self::wrap($action));
    }

    /**
     * Wrap the action given to us by the user to allow for dependency injection and nicer callable
     * syntax.
     *
     * @param $callback
     *
     * @return Closure
     */
    protected static function wrap($callback)
    {
        return function () use ($callback) {
            Route::prepareResponse(app('request'), app()->call($callback))->sendContent();
        };
    }
}
