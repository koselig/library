<?php
namespace Koselig\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Koselig\Support\Wordpress;

/**
 * Archive page route, used when Wordpress reports
 * this page is an archive page.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class ArchiveRoute extends Route
{
    /**
     * Post types for this archive route to hook onto.
     *
     * @var array
     */
    private $postTypes;

    /**
     * Create a new Route instance.
     *
     * @param  array|string $methods
     * @param  array $postTypes
     * @param  \Closure|array $action
     *
     * @return void
     */
    public function __construct($methods, $postTypes, $action)
    {
        parent::__construct($methods, $postTypes, $action);

        $this->postTypes = $this->uri;
        $this->uri = 'archive/' . (implode('/', $this->postTypes) ?: 'all');
    }

    /**
     * Determine if the route matches given request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  bool $includingMethod
     *
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        if (!empty($this->getAction()['domain']) && !Wordpress::multisite($this->getAction()['domain'])) {
            return false;
        }

        return Wordpress::archive($this->postTypes);
    }
}
