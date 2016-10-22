<?php
namespace Koselig\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Koselig\Support\Wordpress;

/**
 * Table containing all terms used by Wordpress.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Term extends Model
{
    protected $primaryKey = 'term_id';
    protected $table = DB_PREFIX . 'terms';
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
            $this->setTable(DB_PREFIX . Wordpress::getSiteId() . '_terms');
        }
    }

    /**
     * Get the taxonomy for this term.
     *
     * @return HasMany
     */
    public function taxonomy()
    {
        return $this->hasMany(TermTaxonomy::class, 'term_id');
    }
}
