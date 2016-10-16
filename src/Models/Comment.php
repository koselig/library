<?php
namespace Koselig\Models;

use Illuminate\Database\Eloquent\Model;
use Koselig\Support\Wordpress;

/**
 * Table containing all the comments belonging to posts.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Comment extends Model
{
    protected $table = DB_PREFIX . 'comments';
    protected $primaryKey = 'comment_ID';
    protected $dates = ['comment_date', 'comment_date_gmt'];
    public $timestamps = false;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set the current table to the site's own table if we're in a multisite
        if (Wordpress::multisite() && (Wordpress::getSiteId() !== 0 && Wordpress::getSiteId() !== 1)) {
            $this->setTable(DB_PREFIX . Wordpress::getSiteId() . '_comments');
        }
    }

    /**
     * Get the post that this comment belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'comment_post_ID');
    }

    /**
     * Get the user that posted this comment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
