<?php
namespace Koselig\Support;

/**
 * Action helper class, nice interface over Wordpress' filter/action functionality.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Action
{
    /**
     * Hook a function or method to a specific filter action.
     *
     * @param $tag
     * @param callable $function
     * @param int $priority
     * @param int $accepted_args
     */
    public static function hook($tag, callable $function, $priority = 10, $accepted_args = 1)
    {
        add_filter($tag, $function, $priority, $accepted_args);
    }

    /**
     * Run all filters hooked to <pre>$tag</pre> on the given <pre>$value</pre>.
     *
     * @param string $tag tag to run
     * @param mixed $value value to run filters on
     * @param array ...$params extra params to pass to filters
     * @return mixed|void
     */
    public static function filter($tag, $value, ...$params)
    {
        return apply_filters($tag, $value, ...$params);
    }

    /**
     * Execute functions hooked on a specific action hook.
     *
     * @param string $tag name of the action to be executed
     * @param array $params parameters to pass to the hooked functions
     */
    public static function trigger($tag, ...$params)
    {
        return do_action($tag, $params);
    }
}
