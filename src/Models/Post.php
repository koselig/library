<?php
namespace Koselig\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Koselig\Exceptions\UnsatisfiedDependencyException;
use Koselig\Support\Action;
use Koselig\Support\Wordpress;
use WP_Post;

/**
 * Table containing all the items within the CMS.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Post extends Model
{
    public $timestamps = false;
    protected $table = DB_PREFIX . 'posts';
    protected $primaryKey = 'ID';
    protected $dates = ['post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt'];
    protected $prefix = DB_PREFIX;

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
            $this->prefix = DB_PREFIX . Wordpress::getSiteId() . '_';
            $this->setTable($this->prefix . 'posts');
        }
    }

    /**
     * Get all the posts within a certain post type.
     *
     * @param Builder $query query to add the scope to
     * @param string $name name of the post type
     *
     * @return Builder
     */
    public function scopePostType($query, $name)
    {
        return $query->where('post_type', $name);
    }

    /**
     * Get a post by its slug.
     *
     * @param $slug
     *
     * @return static
     */
    public static function findBySlug($slug)
    {
        return static::where('post_name', $slug)->first();
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
     * Get all the comments that belong to this post.
     *
     * @return HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'comment_post_ID');
    }

    /**
     * Get meta values for this Post.
     *
     * @param array|string|null $key
     *
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
     *
     * @return array array of ACF values.
     */
    public function getACF($key = null, $format = true)
    {
        if (! function_exists('acf_format_value')) {
            throw new UnsatisfiedDependencyException('Advanced Custom Fields must be installed to use field');
        }

        $meta = $this->getMeta($key);

        if (! is_array($meta)) {
            $meta = [$key => $meta];
            $wantsArray = false;
        } else {
            $wantsArray = true;
        }

        foreach ($meta as $k => $value) {
            $field = $this->getMeta('_' . $k);

            if (! acf_is_field_key($field)) {
                unset($meta[$k]);
                continue;
            }

            if (is_serialized($value)) {
                $value = @unserialize($value);
            }

            $field = get_field_object($field, $k, false, false);

            // unset subfields if the user didn't ask for it specifically
            if (((is_array($key) && ! in_array($k, $key, true)) && $k !== $key || $key === null) && acf_is_sub_field($field)) {
                unset($meta[$k]);
                continue;
            }

            $value = Action::filter('acf/load_value', $value, $k, $field);
            $value = Action::filter('acf/load_value/type=' . $field['type'], $value, $this->ID, $field);
            $value = Action::filter('acf/load_value/name=' . $field['_name'], $value, $this->ID, $field);
            $value = Action::filter('acf/load_value/key=' . $field['key'], $value, $this->ID, $field);

            if ($format) {
                $value = acf_format_value($value, $this->ID, $field);
            }

            $meta[$k] = $value;
        }

        return $wantsArray ? $meta : collect($meta)->first();
    }

    /**
     * Get the filtered title.
     *
     * @return string
     */
    public function getTitleAttribute()
    {
        return Action::filter('the_title', $this->post_title, $this->ID);
    }

    /**
     * Get the excerpt of this post.
     *
     * @return string
     */
    public function getExcerptAttribute()
    {
        return Action::filter('get_the_excerpt', $this->post_excerpt);
    }

    /**
     * Get the filtered content of this post.
     *
     * @return string
     */
    public function getContentAttribute()
    {
        return str_replace(']]>', ']]&gt;', Action::filter('the_content', $this->post_content));
    }

    /**
     * Get the categories of this post.
     *
     * @return Term[]|Collection
     */
    public function getCategoriesAttribute()
    {
        return $this->terms()->whereHas('taxonomy', function ($query) {
            $query->where('taxonomy', 'category');
        })->get();
    }

    /**
     * Get the permalink for this post.
     *
     * @see get_permalink
     *
     * @return false|string
     */
    public function getLinkAttribute()
    {
        return get_permalink($this->toWordpressPost());
    }

    /**
     * Get the tags of this post.
     *
     * @return Term[]|Collection
     */
    public function getTagsAttribute()
    {
        return $this->terms()->whereHas('taxonomy', function ($query) {
            $query->where('taxonomy', 'post_tag');
        })->get();
    }

    /**
     * Get the thumbnail of this post.
     *
     * @see get_the_post_thumbnail
     *
     * @return string
     */
    public function getThumbnailAttribute()
    {
        return $this->thumbnail();
    }

    /**
     * Get the thumbnail of this post.
     *
     * @see get_the_post_thumbnail
     *
     * @param string $size
     *
     * @return string
     */
    public function thumbnail($size = 'post-thumbnail')
    {
        return get_the_post_thumbnail_url($this->toWordpressPost(), $size);
    }

    /**
     * Get the all the terms of this post.
     *
     * @return BelongsToMany
     */
    public function terms()
    {
        return $this->belongsToMany(Term::class, $this->prefix . 'term_relationships', 'object_id', 'term_taxonomy_id');
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

    /**
     * Get the classes that should be applied to this post.
     *
     * @see get_post_class
     *
     * @return string
     */
    public function getClassesAttribute()
    {
        return implode(' ', get_post_class('', $this->toWordpressPost()));
    }

    /**
     * Get the {@link WP_Post} instance for this Post.
     *
     * @deprecated
     *
     * @return WP_Post
     */
    public function toWordpressPost()
    {
        return new WP_Post((object) $this->toArray());
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('published', function (Builder $builder) {
            $builder->where('post_status', 'publish');
        });
    }
}
