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
     * @param string $function
     * @param int $priority
     * @param int $acceptedArgs
     */
    public static function hook($tag, $function, $priority = 10, $acceptedArgs = 1)
    {
        if (!function_exists('add_filter')) {
            // we need to register this hook before wp-settings.php is included, which also means
            // that add_filter hasn't been included yet so we have to add to the wp_filter global
            // manually.
            $GLOBALS['wp_filter'][$tag][$priority][] = [
                'function' => function (...$args) use ($function) {
                    return app()->call($function, $args);
                },
                'accepted_args' => $acceptedArgs,
            ];

            return;
        }

        add_filter($tag, function (...$args) use ($function) {
            return app()->call($function, $args);
        }, $priority, $acceptedArgs);
    }

    /**
     * Run all filters hooked to <pre>$tag</pre> on the given <pre>$value</pre>.
     *
     * @param string $tag tag to run
     * @param mixed $value value to run filters on
     * @param array ...$params extra params to pass to filters
     *
     * @return mixed
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
        return do_action($tag, ...$params);
    }
}
