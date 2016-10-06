<?php
namespace Koselig\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Table containing all the users within the CMS.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class User extends Model implements AuthenticatableContract
{
    use Authenticatable;

    public $table = DB_PREFIX . 'users';
    public $primaryKey = 'ID';

    /**
     * Get all the posts that belong to this user.
     *
     * @return HasMany
     */
    public function posts()
    {
        return $this->hasMany(self::class, 'post_author');
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
}
