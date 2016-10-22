<?php

namespace Koselig\Models;

use Illuminate\Database\Eloquent\Model;
use Koselig\Support\Wordpress;

/**
 * Table containing all Wordpress options.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Option extends Model
{
    protected $primaryKey = 'option_id';
    protected $table = DB_PREFIX.'options';
    public $timestamps = false;

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     *
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set the current table to the site's own table if we're in a multisite
        if (Wordpress::multisite() && (Wordpress::getSiteId() !== 0 && Wordpress::getSiteId() !== 1)) {
            $this->setTable(DB_PREFIX.Wordpress::getSiteId().'_options');
        }
    }

    /**
     * Get an option by its name.
     *
     * @param $name
     *
     * @return mixed
     */
    public static function findByName($name)
    {
        return static::where('option_name', $name)->first();
    }

    /**
     * Get the option's value.
     *
     * @param $value
     *
     * @return mixed
     */
    public function getOptionValueAttribute($value)
    {
        return is_serialized($value) ? unserialize($value) : $value;
    }
}
