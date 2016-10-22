<?php
namespace Koselig\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Request;
use Koselig\Models\User;
use Koselig\Support\Action;
use WP_Error;

/**
 * Wordpress user guard, provides a bridge between Laravel's authentication
 * and Wordpress.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class WordpressGuard implements StatefulGuard
{
    use GuardHelpers;

    /**
     * Get the last user we attempted to login as.
     *
     * @var User
     */
    private $lastAttempted = null;

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return is_user_logged_in();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (! is_null($this->user)) {
            return $this->user;
        }

        return $this->user = ($this->check() ? User::find(get_current_user_id()) : null);
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     *
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $user = wp_authenticate($credentials['username'], $credentials['password']);

        $this->lastAttempted = User::find($user->ID);

        return ! ($user instanceof WP_Error);
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array $credentials
     * @param  bool $remember
     * @param  bool $login
     *
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false, $login = true)
    {
        $validate = $this->validate($credentials);

        if (! $login) {
            return $validate;
        }

        $user = $this->lastAttempted;

        // check if we should use a secure cookie
        wp_set_auth_cookie($user->ID, $credentials['remember'], Request::secure());
        Action::trigger('wp_login', $user->user_login, $user);

        $this->setUser($user);

        return true;
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param  array $credentials
     *
     * @return bool
     */
    public function once(array $credentials = [])
    {
        if ($this->validate($credentials)) {
            $this->setUser($this->lastAttempted);

            return true;
        }

        return false;
    }

    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  bool $remember
     *
     * @return void
     */
    public function login(Authenticatable $user, $remember = false)
    {
        wp_set_auth_cookie($user->ID, $remember);
        Action::trigger('wp_login', $user->user_login, get_userdata($user->ID));
        wp_set_current_user($user->ID);

        $this->user = $user;
    }

    /**
     * Log the given user ID into the application.
     *
     * @param  mixed $id
     * @param  bool $remember
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|bool
     */
    public function loginUsingId($id, $remember = false)
    {
        $user = User::find($id);

        if (! $user) {
            return false;
        }

        wp_set_auth_cookie($user->ID, $remember);
        Action::trigger('wp_login', $user->user_login, get_userdata($user->ID));
        wp_set_current_user($user->ID);

        return $this->user = $user;
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * @param  mixed $id
     *
     * @return bool
     */
    public function onceUsingId($id)
    {
        $user = User::find($id);

        if (! $user) {
            return false;
        }

        wp_set_current_user($id);

        return $this->user = $user;
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember()
    {
        return Request::hasCookie(Request::secure() ? SECURE_AUTH_COOKIE : AUTH_COOKIE);
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout()
    {
        wp_logout();
        $this->user = null;
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     *
     * @return $this
     */
    public function setUser(Authenticatable $user)
    {
        wp_set_current_user($user->ID);
        $this->user = $user;

        return $this;
    }
}
