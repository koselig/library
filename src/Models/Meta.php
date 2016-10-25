<?php
namespace Koselig\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Koselig\Exceptions\UnsatisfiedDependencyException;
use Koselig\Support\Action;
use Koselig\Support\Wordpress;
use Watson\Rememberable\Rememberable;

/**
 * Table containing all metadata about a post.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Meta extends Model
{
    use Rememberable;

    public $timestamps = false;

    /**
     * Length of time to cache this model for.
     *
     * @var integer
     */
    protected $rememberFor;

    /**
     * Cache for all meta values.
     *
     * @var array
     */
    public static $cache = [];
    protected $primaryKey = 'meta_id';
    protected $table = DB_PREFIX . 'postmeta';

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
            $this->setTable(DB_PREFIX . Wordpress::getSiteId() . '_postmeta');
        }

        // enable caching if the user has opted for it in their configuration
        if (config('wordpress.caching')) {
            $this->rememberFor = config('wordpress.caching');
        } else {
            unset($this->rememberFor);
        }
    }

    /**
     * Get metadata for a page (or the current page).
     *
     * <code>Meta::get('my_meta_key');</code>
     * or
     * <code>Meta::get(7, 'my_meta_key');</code>
     *
     * @param int|string|null|Post $page page to get meta for (or name of the meta item to get
     *                                   if you want to get the current page's meta)
     * @param string|null $name
     *
     * @return mixed
     */
    public static function get($page = null, $name = null)
    {
        if (is_object($page) && (is_subclass_of($page, Post::class) || $page instanceof Post)) {
            $page = $page->ID;
        }

        if (!ctype_digit((string) $page) && $name === null) {
            $name = $page;
            $page = null;
        }

        if ($page === null) {
            $page = Wordpress::id();
        }

        if (!isset(static::$cache[$page])) {
            // get all the meta values for a post, it's more than likely we're going to
            // need this again query, so we'll just grab all the results and cache them.
            static::$cache[$page] = static::where('post_id', $page)->get();
        }

        if ($name === null) {
            return static::$cache[$page]->mapWithKeys(function ($item) {
                return [$item->meta_key => $item->meta_value];
            })->all();
        }

        $value = static::$cache[$page]->where('meta_key', $name)->first();

        return empty($value) ? null : $value->meta_value;
    }

    /**
     * Grab an ACF field from the database.
     *
     * @see Meta::get()
     *
     * @param int|string|null|Post $page page to get meta for (or name of the meta item to get
     *                                   if you want to get the current page's meta)
     * @param string|null $name
     * @param bool $format whether to format this field or not
     *
     * @throws UnsatisfiedDependencyException
     *
     * @return mixed
     */
    public static function acf($page = null, $name = null, $format = true)
    {
        if (!function_exists('acf_format_value')) {
            throw new UnsatisfiedDependencyException('Advanced Custom Fields must be installed to use field');
        }

        if (is_object($page) && (is_subclass_of($page, Post::class) || $page instanceof Post)) {
            $page = $page->ID;
        }

        if (!ctype_digit((string) $page) && $name === null) {
            $name = $page;
            $page = null;
        }

        if ($page === null) {
            $page = Wordpress::id();
        }

        $value = static::get($page, $name);

        if (is_serialized($value)) {
            $value = @unserialize($value);
        }

        $field = static::get($page, '_' . $name);

        if (!acf_is_field_key($field)) {
            return;
        }

        $field = get_field_object($field, $name, false, false);
        $value = Action::filter('acf/load_value', $value, $page, $field);
        $value = Action::filter('acf/load_value/type=' . $field['type'], $value, $page, $field);
        $value = Action::filter('acf/load_value/name=' . $field['_name'], $value, $page, $field);
        $value = Action::filter('acf/load_value/key=' . $field['key'], $value, $page, $field);

        if ($format) {
            $value = acf_format_value($value, $page, $field);
        }

        return $value;
    }

    /**
     * Get the post that this meta value belongs to.
     *
     * @return BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
