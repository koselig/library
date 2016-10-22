<?php
namespace Koselig\Mail;

use Illuminate\Support\ServiceProvider;

/**
 * Override Wordpress' wp_mail function to use the Laravel mailer.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class WordpressMailServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function register()
    {
        include_once 'Mailer.php';
    }
}
