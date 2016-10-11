<?php
namespace Koselig\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Table containing all the items within the CMS.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Post extends Model
{
    public $table = DB_PREFIX . 'posts';
    public $primaryKey = 'ID';

    /**
     * Get all the posts within a certain post type.
     *
     * @param Builder $query query to add the scope to
     * @param string $name name of the post type
     * @return Builder
     */
    public function scopePostType($query, $name)
    {
        return $query->where('post_type', $name);
    }

    /**
     * Get all the posts which are published.
     *
     * @param Builder $query query to add the scope to
     * @return Builder
     */
    public function scopePublished($query)
    {
        return $query->where('post_status', 'publish');
    }

    /**
     * Get all the meta values that belong to this post.
     *
     * @return HasMany
     */
    public function meta()
    {
        return $this->hasMany(Meta::class);
    }

    /**
     * Get meta values for this Post.
     *
     * @param array|string|null $key
     * @return mixed
     */
    public function getMeta($key = null)
    {
        /*
         * @var Collection
         */
        $meta = $this->meta;

        if (is_array($key)) {
            $meta = $meta->whereIn('meta_key', $key);
        } elseif (is_string($key)) {
            $meta = $meta->where('meta_key', $key)->first();

            return $meta ? $meta->meta_value : null;
        }

        return $meta->mapWithKeys(function ($item) {
            return [$item->meta_key => $item->meta_value];
        })->all();
    }

    /**
     * Get the author that this post belongs to.
     *
     * @return BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'post_author');
    }
}
