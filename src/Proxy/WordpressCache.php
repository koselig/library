<?php
namespace Koselig\Proxy;

use Cache;
use Illuminate\Cache\TaggableStore;

/**
 * Replace Wordpress' caching with Laravel's driver for consistency and, in many cases, speed.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class WordpressCache
{
    /**
     * The amount of times the cache data was already stored in the cache.
     *
     * @var int
     */
    protected $cacheHits = 0;

    /**
     * Track how may requests were not cached
     *
     * @var int
     */
    protected $cacheMisses = 0;

    /**
     * Prefix to use for non-global cache items.
     *
     * @var string
     */
    protected $blogPrefix = '';

    /**
     * List of global groups.
     *
     * @var array
     */
    protected $globalGroups = [];

    /**
     * Cache items to not persist in our cache.
     *
     * @var array
     */
    protected $nonPersistentGroups = [];

    /**
     * Datastore for non-persistent items.
     *
     * @var array
     */
    protected $nonPersistentStore = [];

    /**
     * Prefix used for anything cached using the Wordpress API so it doesn't conflict
     * with our normal caching.
     */
    const PREFIX = 'wp';

    /**
     * WordpressCache constructor.
     */
    public function __construct()
    {
        $this->blogPrefix = is_multisite() ? get_current_blog_id() : '';
    }

    /**
     * Switches the internal blog ID used for non-global caching.
     *
     * @param int $blog blog to switch to
     */
    public function switchBlog($blog)
    {
        $this->blogPrefix = is_multisite() ? $blog : '';
    }

    /**
     * Adds a value to cache.
     *
     * If the specified key already exists, the value is not stored and the function
     * returns false.
     *
     * @param   string $key The key under which to store the value.
     * @param   mixed $value The value to store.
     * @param   string $group The group value appended to the $key.
     * @param   int $expiration The expiration time, defaults to 0.
     * @return  bool Returns TRUE on success or FALSE on failure.
     */
    public function add($key, $value, $group = 'default', $expiration = 0)
    {
        if (wp_suspend_cache_addition()) {
            return false;
        }

        if (in_array($group, $this->nonPersistentGroups)) {
            if (!isset($this->nonPersistentStore[$group])) {
                $this->nonPersistentStore[$group] = [];
            } elseif (isset($this->nonPersistentStore[$group][$key])) {
                return false;
            }

            $this->nonPersistentStore[$group][$key] = $value;
            return true;
        }

        if (static::getCache($group)->has($this->buildKey($group, $key))) {
            return false;
        }

        if ($expiration === 0) {
            static::getCache($group)->forever($this->buildKey($group, $key), $value);
            return true;
        }

        return static::getCache($group)->add($this->buildKey($group, $key), $value, $expiration / 60);
    }

    /**
     * Replace a value in the cache.
     *
     * If the specified key doesn't exist, the value is not stored and the function
     * returns false.
     *
     * @param   string $key The key under which to store the value.
     * @param   mixed $value The value to store.
     * @param   string $group The group value appended to the $key.
     * @param   int $expiration The expiration time, defaults to 0.
     * @return  bool Returns TRUE on success or FALSE on failure.
     */
    public function replace($key, $value, $group = 'default', $expiration = 0)
    {
        if (in_array($group, $this->nonPersistentGroups)) {
            if (!isset($this->nonPersistentStore[$group][$key])) {
                return false;
            }

            $this->nonPersistentStore[$group][$key] = $value;
            return true;
        }

        if (!static::getCache($group)->has($this->buildKey($group, $key))) {
            return false;
        }

        if ($expiration === 0) {
            static::getCache($group)->forever($this->buildKey($group, $key), $value);
            return true;
        }

        static::getCache($group)->put($this->buildKey($group, $key), $value, $expiration / 60);
        return true;
    }

    /**
     * Remove the item from the cache.
     *
     * @param   string $key The key under which to store the value.
     * @param   string $group The group value appended to the $key.
     * @return  bool  Returns TRUE on success or FALSE on failure.
     */
    public function delete($key, $group = 'default')
    {
        if (in_array($group, $this->nonPersistentGroups)) {
            if (isset($this->nonPersistentStore[$group][$key])) {
                unset($this->nonPersistentStore[$group][$key]);
            }

            return true;
        }

        return static::getCache($group)->forget($this->buildKey($group, $key));
    }

    /**
     * Invalidate all items in the cache.
     *
     * @return  bool  Returns TRUE on success or FALSE on failure.
     */
    public function flush()
    {
        $this->nonPersistentStore = [];

        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(sprintf('%s:%s', static::PREFIX, config('cache.prefix')))->flush();
        } else {
            // TODO: damn... cache driver doesn't support tags, should we loop over all cache elements and
            // delete anything that starts with our prefix?
            Cache::flush();
        }

        return true;
    }

    /**
     * Retrieve object from cache.
     *
     * Gets an object from cache based on $key and $group.
     *
     * @param   string $key The key under which to store the value.
     * @param   string $group The group value appended to the $key.
     * @param   boolean $force Optional. Ignored
     * @param   bool &$found Optional. Whether the key was found in the cache. Disambiguates a return of
     *                                    false, a storable value. Passed by reference. Default null.
     * @return  bool|mixed                Cached object value.
     */
    public function get($key, $group = 'default', $force = false, &$found = null)
    {
        if (in_array($group, $this->nonPersistentGroups)) {
            if (!isset($this->nonPersistentStore[$group][$key])) {
                $found = false;
                return false;
            }

            $found = true;
            return $this->nonPersistentStore[$group][$key];
        }

        $key = $this->buildKey($group, $key);

        if (static::getCache($group)->has($key)) {
            $this->cacheHits++;
            $found = true;
        } else {
            $this->cacheMisses++;
            $found = false;
            return false;
        }

        return maybe_unserialize(static::getCache($group)->get($key));
    }

    /**
     * Increment a counter by the amount specified
     *
     * @param  string $key
     * @param  int $offset
     * @param  string $group
     * @return int|bool False on failure, the item's new value on success.
     */
    public function incr($key, $offset = 1, $group = 'default')
    {
        if (in_array($group, $this->nonPersistentGroups)) {
            if (!isset($this->nonPersistentStore[$group])) {
                $this->nonPersistentStore[$group] = [];
            }

            if (!isset($this->nonPersistentStore[$group][$key])) {
                $this->nonPersistentStore[$group][$key] = 0;
            }

            $this->nonPersistentStore[$group][$key]++;
            return true;
        }

        static::getCache($group)->increment($this->buildKey($group, $key), $offset);

        return static::getCache($group)->get($this->buildKey($group, $key));
    }

    /**
     * Decrease a counter by the amount specified
     *
     * @param  string $key
     * @param  int $offset
     * @param  string $group
     * @return int|bool False on failure, the item's new value on success.
     */
    public function decr($key, $offset = 1, $group = 'default')
    {
        if (in_array($group, $this->nonPersistentGroups)) {
            if (!isset($this->nonPersistentStore[$group])) {
                $this->nonPersistentStore[$group] = [];
            }

            if (!isset($this->nonPersistentStore[$group][$key])) {
                $this->nonPersistentStore[$group][$key] = 0;
            }

            $this->nonPersistentStore[$group][$key]--;
            return true;
        }

        static::getCache($group)->decrement($this->buildKey($group, $key), $offset);

        return static::getCache($group)->get($this->buildKey($group, $key));
    }

    /**
     * Sets a value in cache.
     *
     * The value is set whether or not this key already exists in our store.
     *
     * @param   string $key The key under which to store the value.
     * @param   mixed $value The value to store.
     * @param   string $group The group value appended to the $key.
     * @param   int $expiration The expiration time, defaults to 0.
     * @return  bool               Returns TRUE on success or FALSE on failure.
     */
    public function set($key, $value, $group = 'default', $expiration = 0)
    {
        if (in_array($group, $this->nonPersistentGroups)) {
            if (!isset($this->nonPersistentStore[$group])) {
                $this->nonPersistentStore[$group] = [];
            }

            $this->nonPersistentStore[$group][$key] = $value;
            return true;
        }

        if ($expiration === 0) {
            static::getCache($group)->forever($this->buildKey($group, $key), $value);
            return true;
        }

        static::getCache($group)->put($this->buildKey($group, $key), $value, $expiration / 60);

        return true;
    }

    /**
     * Sets the list of global cache groups.
     *
     * @param array $groups List of groups that are global.
     */
    public function addGlobalGroups($groups)
    {
        $this->globalGroups = array_merge($this->globalGroups, (array) $groups);
    }

    /**
     * Sets the list of non-persistent cache groups
     *
     * @param array $groups List of groups that should not be persisted.
     */
    public function addNonPersistentGroups($groups)
    {
        $this->nonPersistentGroups = array_merge($this->nonPersistentGroups, (array) $groups);
    }

    /**
     * Get the cache driver we should be saving to and add a tag if the driver supports it.
     *
     * @param string $group
     * @return Cache|\Illuminate\Cache\TaggedCache
     */
    protected static function getCache($group = 'default')
    {
        return Cache::getStore() instanceof TaggableStore ? Cache::tags([
            sprintf('%s:%s:%s', static::PREFIX . config('cache.prefix'), $group ?: 'default'),
            sprintf('%s:%s', static::PREFIX, config('cache.prefix'))
        ]) : cache();
    }

    /**
     * Build a unique key for this cache object.
     *
     * @param string $group The group value appended to the $key.
     * @param string $key The key under which to store the value.
     * @return string
     */
    protected function buildKey($group = 'default', $key)
    {
        if (empty($group)) {
            $group = 'default';
        }

        $prefix = (is_multisite() && !in_array($group, $this->globalGroups)) ? ($this->blogPrefix . ':') : '';

        return sprintf('%s:%s%s:%s', static::PREFIX, $prefix, $group, $key);
    }
}
