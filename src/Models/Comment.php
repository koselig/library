<?php
namespace Koselig\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Koselig\Support\Action;
use Koselig\Support\Wordpress;

/**
 * Table containing all the comments belonging to posts.
 *
 * @property integer $comment_ID ID of this comment
 * @property integer $comment_post_ID ID of the post this comment belongs to
 * @property string $comment_author Author of this comment
 * @property string $comment_author_email Author of this comment's email
 * @property string $comment_author_url Author of this comment's URL
 * @property string $comment_author_IP Author of this comment's IP
 * @property Carbon $comment_date Date this comment was posted
 * @property Carbon $comment_date_gmt Date this comment was posted
 * @property string $comment_content Content of this comment
 * @property integer $comment_karma Karma of this comment
 * @property string $content Content of the comment filtered through "comment_text"
 * @property boolean $comment_approved Whether or not this comment has been approved
 * @property string $comment_agent
 * @property string $comment_type
 * @property integer $comment_parent comment this comment was in reply to
 * @property integer $user_id user this comment belongs to
 * @property-read Post $post post this comment belongs to
 * @property-read User $user user this comment belongs to
 * @property-read Comment $parent comment this comment is in reply to
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
     * Comment filtered through the "comment_text" filters.
     *
     * @return string
     */
    public function getContentAttribute()
    {
        return Action::filter('comment_text', $this->comment_content);
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

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'comment_parent');
    }
}
