<?php
namespace Koselig\Models;

use Illuminate\Database\Eloquent\Model;
use Koselig\Support\Wordpress;

/**
 * Taxonomy for the terms in the CMS.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class TermTaxonomy extends Model
{
    protected $primaryKey = 'term_taxonomy_id';
    protected $table = DB_PREFIX . 'term_taxonomy';
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
            $this->setTable(DB_PREFIX . Wordpress::getSiteId() . '_term_taxonomy');
        }
    }
}
