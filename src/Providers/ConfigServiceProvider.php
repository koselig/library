<?php
namespace Koselig\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Lets Laravel know about the configuration files we have to publish.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class ConfigServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            realpath(__DIR__ . '/../../config/templates.php') => config_path('templates.php'),
            realpath(__DIR__ . '/../../config/wordpress.php') => config_path('wordpress.php'),
            realpath(__DIR__ . '/../../config/posttypes.php') => config_path('posttypes.php'),
            realpath(__DIR__ . '/../../config/supports.php') => config_path('supports.php'),
        ]);
    }
}
