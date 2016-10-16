<?php
namespace Koselig\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Table containing all Wordpress options.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Option extends Model
{
    protected $primaryKey = 'option_id';
    protected $table = DB_PREFIX . 'options';
    public $timestamps = false;

    /**
     * Get an option by its name.
     *
     * @param $name
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
     * @return mixed
     */
    public function getOptionValueAttribute($value)
    {
        return is_serialized($value) ? unserialize($value) : $value;
    }
}
