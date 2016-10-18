<?php
namespace Koselig\Proxy;

use Illuminate\Support\Str;
use WP_Comment;
use WP_Post;
use WP_Query;

/**
 * Proxies the {@link \WP_Query} class from Wordpress for a more elegant syntax.
 *
 * @method void init Initiates object properties and sets default values.
 * @method void parseQueryVars Reparse the query vars.
 * @method array fillQueryVars Fills in the query variables, which do not exist within the parameter.
 * @method void parseQuery Parse a query string and set query type booleans.
 * @method void parseTaxQuery Parses various taxonomy related query vars.
 * @method void set404 Sets the 404 property and saves whether query is feed.
 * @method mixed get Retrieve query variable.
 * @method void set Set query variable.
 * @method array posts Retrieve the posts based on query variables.
 * @method WP_Post nextPost Set up the next post and iterate current post index.
 * @method WP_Post post Sets up the current post.
 * @method bool hasPosts Determines whether there are more posts available in the loop.
 * @method void rewindPosts Rewind the posts and reset post index.
 * @method WP_Comment nextComment Iterate current comment index and return WP_Comment object.
 * @method WP_Comment comment Sets up the current comment.
 * @method bool hasComments Whether there are more comments available.
 * @method void rewindComments Rewind the comments, resets the comment index and comment to first.
 * @method array query Sets up the WordPress query by parsing query string.
 * @method object queriedObject Retrieve queried object.
 * @method int queriedObjectId Retrieve ID of the current queried object.
 * @method bool archive Is the query for an existing archive page?
 * @method bool postTypeArchive Is the query for an existing post type archive page?
 * @method bool attachment Is the query for an existing attachment page?
 * @method bool author Is the query for an existing author archive page?
 * @method bool category Is the query for an existing category archive page?
 * @method bool tag Is the query for an existing tag archive page?
 * @method bool tax Is the query for an existing custom taxonomy archive page?
 * @method bool date Is the query for an existing date archive?
 * @method bool day Is the query for an existing day archive?
 * @method bool feed Is the query for a feed?
 * @method bool commentFeed Is the query for a comments feed?
 * @method bool frontPage Is the query for the front page of the site?
 * @method bool home Is the query for the blog homepage?
 * @method bool month Is the query for an existing month archive?
 * @method bool page Is the query for an existing single page?
 * @method bool paged Is the query for paged result and not for the first page?
 * @method bool preview Is the query for a post or page preview?
 * @method bool robots Is the query for the robots file?
 * @method bool search Is the query for a search?
 * @method bool single Is the query for an existing single post?
 * @method bool singular Is the query for an existing single post of any post type (post, attachment, page, ...)?
 * @method bool time Is the query for a specific time?
 * @method bool trackback Is the query for a trackback endpoint call?
 * @method bool year Is the query for an existing year archive?
 * @method bool 404 Is the query a 404 (returns no results)?
 * @method bool embed Is the query for an embedded post?
 * @method bool mainQuery Is the query the main query?
 * @method true setupPostdata Set up global post data.
 * @method void resetPostdata After looping through a query, restores the $post global to the current post in this obj.
 * @method mixed lazyloadTermMeta Lazyload term meta for posts in the loop.
 * @method mixed lazyloadCommentMeta Lazyload comment meta for comments in the loop.
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class Query
{
    /**
     * Current query.
     *
     * @var WP_Query
     */
    private $query;

    public static function instance(WP_Query $query)
    {
        $instance = new static;
        $instance->query = $query;
        return $instance;
    }

    /**
     * Get a property from {@link WP_Query}
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->query->{Str::snake($name)};
    }

    /**
     * Pass a call to this function to {@link WP_Query}
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $name = Str::snake($name);
        $name = str_replace('has', 'have', $name);

        if (!method_exists($this->query, $name)) {
            // try and find the method that was attempted to be called. Makes for a lot nicer code when reading over it.
            if (method_exists($this->query, 'the_' . $name)) {
                $name = 'the_' . $name;
            } elseif (method_exists($this->query, 'is_' . $name)) {
                $name = 'is_' . $name;
            } elseif (method_exists($this->query, 'get_' . $name)) {
                $name = 'get_' . $name;
            }
        }

        return $this->query->{$name}(...$arguments);
    }
}
