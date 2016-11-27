<?php
namespace Koselig\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Koselig\Support\Action;
use Koselig\Support\Wordpress;
use Watson\Rememberable\Rememberable;

/**
 * Table containing all the comments belonging to posts.
 *
 * @property int $comment_ID ID of this comment
 * @property int $comment_post_ID ID of the post this comment belongs to
 * @property string $comment_author Author of this comment
 * @property string $comment_author_email Author of this comment's email
 * @property string $comment_author_url Author of this comment's URL
 * @property string $comment_author_IP Author of this comment's IP
 * @property Carbon $comment_date Date this comment was posted
 * @property Carbon $comment_date_gmt Date this comment was posted
 * @property string $comment_content Content of this comment
 * @property int $comment_karma Karma of this comment
 * @property string $content Content of the comment filtered through "comment_text"
 * @property bool $comment_approved Whether or not this comment has been approved
 * @property string $comment_agent
 * @property string $comment_type
 * @property int $comment_parent comment this comment was in reply to
 * @property int $user_id user this comment belongs to
 * @property Post $post post this comment belongs to
 * @property User $user user this comment belongs to
 * @property Comment $parent comment this comment is in reply to
 * @property Comment[]|Collection $children comments that belong to this comment
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Comment extends Model
{
    use Rememberable;

    public $timestamps = false;
    protected $table = DB_PREFIX . 'comments';
    protected $primaryKey = 'comment_ID';
    protected $dates = ['comment_date', 'comment_date_gmt'];

    /**
     * Length of time to cache this model for.
     *
     * @var int
     */
    protected $rememberFor;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array $attributes
     *
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set the current table to the site's own table if we're in a multisite
        if (Wordpress::multisite() && (Wordpress::getSiteId() !== 0 && Wordpress::getSiteId() !== 1)) {
            $this->setTable(DB_PREFIX . Wordpress::getSiteId() . '_comments');
        }

        // enable caching if the user has opted for it in their configuration
        if (config('wordpress.caching')) {
            $this->rememberFor = config('wordpress.caching');
        } else {
            unset($this->rememberFor);
        }
    }

    /**
     * Get all the comments that belong to this comment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Comment::class, 'comment_parent');
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

    /**
     * Get the comment this comment belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'comment_parent');
    }
}
