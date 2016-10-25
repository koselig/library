<?php
namespace Koselig\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Koselig\Support\Wordpress;
use Watson\Rememberable\Rememberable;

/**
 * Table containing all terms used by Wordpress.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Term extends Model
{
    use Rememberable;

    public $timestamps = false;
    protected $primaryKey = 'term_id';
    protected $table = DB_PREFIX . 'terms';

    /**
     * Length of time to cache this model for.
     *
     * @var integer
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
            $this->setTable(DB_PREFIX . Wordpress::getSiteId() . '_terms');
        }

        // enable caching if the user has opted for it in their configuration
        if (config('wordpress.caching')) {
            $this->rememberFor = config('wordpress.caching');
        } else {
            unset($this->rememberFor);
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
