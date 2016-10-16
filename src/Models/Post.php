<?php
namespace Koselig\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Koselig\Exceptions\UnsatisfiedDependencyException;
use Koselig\Support\Action;

/**
 * Table containing all the items within the CMS.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Post extends Model
{
    public $table = DB_PREFIX . 'posts';
    public $primaryKey = 'ID';

    public $timestamps = false;

    public $dates = ['post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt'];

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
     * Get meta values for this Post.
     *
     * @param array|string|null $key key (or keys) to get or null for all.
     * @param bool $format whether to format this field or not
     * @return array array of ACF values.
     */
    public function getACF($key = null, $format = true)
    {
        if (!function_exists('acf_format_value')) {
            throw new UnsatisfiedDependencyException('Advanced Custom Fields must be installed to use field');
        }

        $meta = $this->getMeta($key);

        foreach ($meta as $key => $value)
        {
            if (is_serialized($value)) {
                $value = @unserialize($value);
            } else {
                unset($meta[$key]);
                continue;
            }

            $field = $this->getMeta('_' . $key);

            if (!acf_is_field_key($field)) {
                unset($meta[$key]);
                continue;
            }

            $field = get_field_object($field, $key, false, false);
            $value = Action::filter('acf/load_value', $value, $key, $field);
            $value = Action::filter('acf/load_value/type=' . $field['type'], $value, $this->ID, $field);
            $value = Action::filter('acf/load_value/name=' . $field['_name'], $value, $this->ID, $field);
            $value = Action::filter('acf/load_value/key=' . $field['key'], $value, $this->ID, $field);

            if ($format) {
                $value = acf_format_value($value, $this->ID, $field);
            }

            $meta[$key] = $value;
        }

        return $meta;
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
