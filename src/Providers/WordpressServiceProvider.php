<?php
namespace Koselig\Providers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Koselig\Guards\WordpressGuard;
use Koselig\Support\Action;

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
        define(
            'WP_PATH',
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
     * Register the Wordpress authentication services.
     *
     * @return void
     */
    public function boot()
    {
        Auth::extend('wordpress', function ($app, $name, array $config) {
            return new WordpressGuard(Auth::createUserProvider($config['provider']));
        });
    }

    /**
     * Set up the configuration values that wp-config.php
     * does. Use all the values out of .env instead.
     *
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return void
     */
    protected function setConfig()
    {
        // Wordpress requires $table_prefix rather than another constant.
        $table_prefix = 'wp_';

        define('WP_DEBUG', $this->app->make('config')->get('app.debug'));
        define('WP_DEBUG_DISPLAY', WP_DEBUG);

        $this->setDatabaseConstants($table_prefix);
        $this->setAuthenticationConstants();
        $this->setLocationConstants();
        $this->setMultisiteConstants();

        if ($this->app->runningInConsole()) {
            // allow wordpress to run, even when running from console (ie. artisan compiling)
            $_SERVER['SERVER_PROTOCOL'] = 'https';
            $_SERVER['HTTP_HOST'] = parse_url(config('app.url'))['host'];
        }

        require ABSPATH . 'wp-settings.php';
    }

    /**
     * Set all the database constants used by Wordpress.
     *
     * @param string $tablePrefix
     */
    private function setDatabaseConstants($tablePrefix)
    {
        $db = DB::getConfig(null);

        define('DB_NAME', $db['database']);
        define('DB_USER', $db['username']);
        define('DB_PASSWORD', $db['password']);
        define('DB_HOST', $db['host']);
        define('DB_CHARSET', $db['charset']);
        define('DB_COLLATE', $db['collation']);
        define('DB_PREFIX', $tablePrefix);
    }

    /**
     * Set all the authentication constants used by Wordpress.
     */
    private function setAuthenticationConstants()
    {
        define('AUTH_KEY', $this->app->make('config')->get('wordpress.auth_key'));
        define('SECURE_AUTH_KEY', $this->app->make('config')->get('wordpress.secure_auth_key'));
        define('LOGGED_IN_KEY', $this->app->make('config')->get('wordpress.logged_in_key'));
        define('NONCE_KEY', $this->app->make('config')->get('wordpress.nonce_key'));
        define('AUTH_SALT', $this->app->make('config')->get('wordpress.auth_salt'));
        define('SECURE_AUTH_SALT', $this->app->make('config')->get('wordpress.secure_auth_salt'));
        define('LOGGED_IN_SALT', $this->app->make('config')->get('wordpress.logged_in_salt'));
        define('NONCE_SALT', $this->app->make('config')->get('wordpress.nonce_salt'));
    }

    /**
     * Set constants to let Wordpress know where it is in relation to the rest
     * of the site, and move the wp_content directory to something a little more "saner"
     * which sort of hides the fact that we are running Wordpress behind the scenes.
     */
    private function setLocationConstants()
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->app->basePath() . DIRECTORY_SEPARATOR . WP_PATH);
        }

        define('WP_SITEURL', $this->app->make(UrlGenerator::class)->to(str_replace('public/', '', WP_PATH)));
        define('WP_HOME', $this->app->make(UrlGenerator::class)->to('/'));

        define('WP_CONTENT_DIR', $this->app->basePath() . DIRECTORY_SEPARATOR . 'public/content');
        define('WP_CONTENT_URL', $this->app->make(UrlGenerator::class)->to('content'));
    }

    /**
     * Set up constants that will allow the user to use a multisite install of Wordpress.
     */
    private function setMultisiteConstants()
    {
        $multisite = $this->app->make('config')->get('wordpress.wp_allow_multisite');

        if ($multisite) {
            define('WP_ALLOW_MULTISITE', $multisite);

            $enabled = $this->app->make('config')->get('wordpress.multisite');

            if ($enabled) {
                define('MULTISITE', $enabled);
                define('SUBDOMAIN_INSTALL', $this->app->make('config')->get('wordpress.subdomain_install'));
                define('DOMAIN_CURRENT_SITE', $this->app->make('config')->get('wordpress.domain_current_site'));
                define('PATH_CURRENT_SITE', $this->app->make('config')->get('wordpress.path_current_site'));
                define('SITE_ID_CURRENT_SITE', $this->app->make('config')->get('wordpress.site_id_current_site'));
                define('BLOG_ID_CURRENT_SITE', $this->app->make('config')->get('wordpress.blog_id_current_site'));
            }
        }
    }

    /**
     * Wordpress core hooks needed for the main functionality of
     * Koselig.
     *
     * @return void
     */
    protected function triggerHooks()
    {
        // register the user's templates
        Action::hook('theme_page_templates', function ($pageTemplates) {
            return array_merge($pageTemplates, config('templates'));
        });

        // hacky fix to get network admin working, wordpress is basing the network admin path off of
        // the default site's main link, which obviously doesn't work when the site and wordpress are in
        // separate directories.
        Action::hook('network_site_url', function ($url, $path, $scheme) {
            if ($scheme == 'relative') {
                $url = get_current_site()->path;
            } else {
                $url = set_url_scheme('http://' . get_current_site()->domain . get_current_site()->path, $scheme);
            }

            if ($path && is_string($path)) {
                $url .= 'cms/' . ltrim($path, '/');
            }

            return $url;
        }, 10, 3);

        $this->registerPostTypes();
    }

    /**
     * Register all the user's custom post types with Wordpress.
     *
     * @return void
     */
    protected function registerPostTypes()
    {
        foreach (config('posttypes') as $key => $value) {
            register_post_type($key, $value);
        }
    }
}
