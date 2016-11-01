<?php
namespace Koselig\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Provide various blade directives to aid in Wordpress view development.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class WordpressTemplatingServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('loop', function () {
            return '<?php if (query()->hasPosts()): while (query()->hasPosts()): '
                . 'query()->post(); $post = post(); ?>';
        });

        Blade::directive('endloop', function () {
            return '<?php endwhile; endif; ?>';
        });

        Blade::directive('wphead', function () {
            return '<?php wp_head(); ?>';
        });

        Blade::directive('wpfooter', function () {
            return '<?php wp_footer(); ?>';
        });

        if (function_exists('gravity_form')) {
            Blade::directive('gravityform', function ($expression) {
                return "<?php gravity_form({$expression}); ?>";
            });
        }
    }
}
