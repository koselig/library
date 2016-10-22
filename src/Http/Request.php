<?php

namespace Koselig\Http;

use Illuminate\Http\Request as BaseRequest;
use Koselig\Models\Post;
use Koselig\Support\Wordpress;

/**
 * Extend the Request class to add some Wordpress-related helpers.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Request extends BaseRequest
{
    /**
     * @var Post
     */
    private $post;

    /**
     * Get the Post instance this request has asked for.
     *
     * @return Post
     */
    public function post()
    {
        return $this->post ?: $this->post = Post::find(Wordpress::id());
    }
}
