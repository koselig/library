<?php
namespace Koselig\Models;

use Illuminate\Database\Eloquent\Model;
use Koselig\Support\Wordpress;
use Watson\Rememberable\Rememberable;

/**
 * Taxonomy for the terms in the CMS.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class TermTaxonomy extends Model
{
    use Rememberable;

    public $timestamps = false;
    protected $primaryKey = 'term_taxonomy_id';
    protected $table = DB_PREFIX . 'term_taxonomy';

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
            $this->setTable(DB_PREFIX . Wordpress::getSiteId() . '_term_taxonomy');
        }

        // enable caching if the user has opted for it in their configuration
        if (config('wordpress.caching')) {
            $this->rememberFor = config('wordpress.caching');
        } else {
            unset($this->rememberFor);
        }
    }
}
