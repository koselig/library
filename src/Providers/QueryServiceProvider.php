<?php
namespace Koselig\Providers;

use Illuminate\Support\ServiceProvider;
use Koselig\Models\Post;
use Koselig\Proxy\Query;

/**
 * Service provider that provides bindings for the several queries that Wordpress
 * has running at once.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class QueryServiceProvider extends ServiceProvider
{
    private $cached = [];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('query', function () {
            // main page query
            return Query::instance($GLOBALS['wp_the_query']);
        });

        $this->app->bind('loop', function () {
            // current post in "The Loop"
            $post = $GLOBALS['post']->ID;

            return $this->cached[$post] ?? $this->cached[$post] = Post::find($post);
        });
    }
}
