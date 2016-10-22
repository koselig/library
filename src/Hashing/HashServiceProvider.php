<?php

namespace Koselig\Hashing;

use Illuminate\Support\ServiceProvider;

/**
 * Provide 'wphash' service to allow for hashing using Wordpress'
 * hashing methods.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class HashServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('wphash', function () {
            return new WordpressHasher();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['wphash'];
    }
}
