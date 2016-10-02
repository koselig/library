<?php

namespace Koselig\Providers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for everything Wordpress, configures
 * everything that needs configuring then boots the backend
 * of Wordpress.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class WordpressServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function register()
    {
        // get the path wordpress is installed in
        define('WP_PATH',
            json_decode(
                file_get_contents($this->app->basePath() . DIRECTORY_SEPARATOR . 'composer.json'),
                true
            )['extra']['wordpress-install-dir'] . '/'
        );

        $this->setConfig();
        $this->triggerHooks();

        // Set up the WordPress query.
        wp();
    }

    /**
     * Set up the configuration values that wp-config.php
     * does. Use all the values out of .env instead.
     *
     * @return void
     */
    protected function setConfig()
    {
        $table_prefix = 'wp_';

        $db = DB::getConfig(null);

        define('DB_NAME', $db['database']);
        define('DB_USER', $db['username']);
        define('DB_PASSWORD', $db['password']);
        define('DB_HOST', $db['host']);
        define('DB_CHARSET', $db['charset']);
        define('DB_COLLATE', $db['collation']);
        define('DB_PREFIX', $table_prefix);

        define('AUTH_KEY', $this->app->make('config')->get('wordpress.auth_key'));
        define('SECURE_AUTH_KEY', $this->app->make('config')->get('wordpress.secure_auth_key'));
        define('LOGGED_IN_KEY', $this->app->make('config')->get('wordpress.logged_in_key'));
        define('NONCE_KEY', $this->app->make('config')->get('wordpress.nonce_key'));
        define('AUTH_SALT', $this->app->make('config')->get('wordpress.auth_salt'));
        define('SECURE_AUTH_SALT', $this->app->make('config')->get('wordpress.secure_auth_salt'));
        define('LOGGED_IN_SALT', $this->app->make('config')->get('wordpress.logged_in_salt'));
        define('NONCE_SALT', $this->app->make('config')->get('wordpress.nonce_salt'));

        define('WP_DEBUG', $this->app->make('config')->get('app.debug'));
        define('SAVEQUERIES', WP_DEBUG);
        define('WP_DEBUG_DISPLAY', WP_DEBUG);
        define('SCRIPT_DEBUG', WP_DEBUG);

        define('DISALLOW_FILE_EDIT', true);

        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->app->basePath() . DIRECTORY_SEPARATOR . WP_PATH);
        }

        define('WP_SITEURL', $this->app->make(UrlGenerator::class)->to(str_replace('public/', '', WP_PATH)));
        define('WP_HOME', $this->app->make(UrlGenerator::class)->to('/'));

        define('WP_CONTENT_DIR', $this->app->basePath() . DIRECTORY_SEPARATOR . 'public/content');
        define('WP_CONTENT_URL', $this->app->make(UrlGenerator::class)->to('content'));

        if ($this->app->runningInConsole()) {
            $_SERVER['SERVER_PROTOCOL'] = 'https';
        }

        require ABSPATH . 'wp-settings.php';
    }

    /**
     * Wordpress core hooks needed for the main functionality of
     * Koselig.
     *
     * @return void
     */
    protected function triggerHooks()
    {
        add_filter('theme_page_templates', function ($page_templates) {
            return array_merge($page_templates, config('templates'));
        });
    }
}
