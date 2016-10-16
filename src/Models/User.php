<?php
namespace Koselig\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Koselig\Support\Wordpress;

/**
 * Table containing all the users within the CMS.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class User extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = DB_PREFIX . 'users';
    protected $primaryKey = 'ID';
    protected $dates = ['user_registered'];
    public $timestamps = false;

    /**
     * Get all the posts that belong to this user.
     *
     * @return HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'post_author');
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->user_pass;
    }

    /**
     * Get a link to this user's author page.
     *
     * @return string
     */
    public function link()
    {
        return get_author_posts_url($this->ID, $this->display_name);
    }
}
