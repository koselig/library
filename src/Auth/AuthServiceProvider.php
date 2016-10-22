<?php

namespace Koselig\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * Register our Wordpress guard with Laravel.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::extend('wordpress', function ($app, $name, array $config) {
            return new WordpressGuard(Auth::createUserProvider($config['provider']));
        });
    }
}
