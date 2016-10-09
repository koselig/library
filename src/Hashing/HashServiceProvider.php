<?php
namespace Koselig\Hashing;

use Illuminate\Support\ServiceProvider;

/**
 * Replace Laravel's hasher with Wordpress'.
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
        $this->app->singleton('hash', function () {
            return new WordpressHasher;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['hash'];
    }
}
