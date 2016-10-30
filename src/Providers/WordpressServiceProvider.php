<?php
namespace Koselig\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Koselig\Support\Action;
use Koselig\Support\Wordpress;
use Request;

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
    }

    /**
     * Bootstrap any application services.
     *
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     *
     * @return void
     */
    public function boot()
    {
        // Wordpress requires $table_prefix rather than another constant.
        $table_prefix = 'wp_';
        $this->setDatabaseConstants($table_prefix);
        require ABSPATH . 'wp-settings.php';

        $this->triggerHooks();

        // Set up the WordPress query.
        wp();

        if (!$this->app->runningInConsole()
            && (defined('WP_ADMIN') || str_contains(Request::server('SCRIPT_NAME'), strrchr(wp_login_url(), '/')))) {
            // disable query caching when in Wordpress admin
            config(['wordpress.caching' => 0]);
        }
    }

    /**
     * Register all of the site's theme support.
     *
     * @return void
     */
    public function addThemeSupport()
    {
        foreach (config('supports') as $key => $value) {
            if (is_string($key)) {
                add_theme_support($key, $value);
            } else {
                add_theme_support($value);
            }
        }
    }

    /**
     * Hacky fix to get network admin working, Wordpress is basing the network admin path off of
     * the default site's main link, which obviously doesn't work when the site and Wordpress are in
     * separate directories.
     *
     * @param $url
     * @param $path
     * @param $scheme
     *
     * @return string
     */
    public function rewriteNetworkUrl($url, $path, $scheme)
    {
        if ($scheme === 'relative') {
            $url = Wordpress::site()->path;
        } else {
            $url = set_url_scheme('http://' . Wordpress::site()->domain . Wordpress::site()->path, $scheme);
        }

        if ($path && is_string($path)) {
            $url .= str_replace('public/', '', WP_PATH) . ltrim($path, '/');
        }

        return $url;
    }

    /**
     * Set up the configuration values that wp-config.php
     * does. Use all the values out of .env instead.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     *
     * @return void
     */
    protected function setConfig()
    {
        define('WP_DEBUG', config('app.debug'));
        define('WP_DEBUG_DISPLAY', WP_DEBUG);
        define('WP_DEFAULT_THEME', 'koselig');
        define('DISALLOW_FILE_MODS', true);

        $this->setAuthenticationConstants();
        $this->setLocationConstants();
        $this->setMultisiteConstants();

        if ($this->app->runningInConsole()) {
            // allow wordpress to run, even when running from console (ie. artisan compiling)
            $_SERVER['SERVER_PROTOCOL'] = 'https';
            $_SERVER['HTTP_HOST'] = parse_url(config('app.url'))['host'];
        }

        // we need to register this hook before wp-settings.php is included, which also means
        // that add_filter hasn't been included yet so we have to add to the wp_filter global
        // manually.
        $GLOBALS['wp_filter']['after_setup_theme'][10][] = [
            'function' => [$this, 'addThemeSupport'],
            'accepted_args' => 0,
        ];
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

        // set the ACF storage path
        Action::hook('acf/settings/save_json', function () {
            return resource_path('fields');
        });

        Action::hook('network_site_url', [$this, 'rewriteNetworkUrl'], 10, 3);

        $this->registerPostTypes();
    }

    /**
     * Register all the site's custom post types with Wordpress.
     *
     * @return void
     */
    protected function registerPostTypes()
    {
        foreach (config('posttypes') as $key => $value) {
            register_post_type($key, $value);
        }
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
        define('AUTH_KEY', config('wordpress.auth_key'));
        define('SECURE_AUTH_KEY', config('wordpress.secure_auth_key'));
        define('LOGGED_IN_KEY', config('wordpress.logged_in_key'));
        define('NONCE_KEY', config('wordpress.nonce_key'));
        define('AUTH_SALT', config('wordpress.auth_salt'));
        define('SECURE_AUTH_SALT', config('wordpress.secure_auth_salt'));
        define('LOGGED_IN_SALT', config('wordpress.logged_in_salt'));
        define('NONCE_SALT', config('wordpress.nonce_salt'));
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

        define('WP_SITEURL', url(str_replace('public/', '', WP_PATH)));
        define('WP_HOME', url('/'));

        define('WP_CONTENT_DIR', $this->app->basePath() . DIRECTORY_SEPARATOR . 'public/content');
        define('WP_CONTENT_URL', url('content'));
    }

    /**
     * Set up constants that will allow the user to use a multisite install of Wordpress.
     */
    private function setMultisiteConstants()
    {
        $multisite = config('wordpress.wp_allow_multisite');

        if ($multisite) {
            define('WP_ALLOW_MULTISITE', $multisite);

            $enabled = config('wordpress.multisite');

            if ($enabled) {
                define('MULTISITE', $enabled);
                define('SUBDOMAIN_INSTALL', config('wordpress.subdomain_install'));
                define('DOMAIN_CURRENT_SITE', config('wordpress.domain_current_site'));
                define('PATH_CURRENT_SITE', config('wordpress.path_current_site'));
                define('SITE_ID_CURRENT_SITE', config('wordpress.site_id_current_site'));
                define('BLOG_ID_CURRENT_SITE', config('wordpress.blog_id_current_site'));
            }
        }
    }
}
