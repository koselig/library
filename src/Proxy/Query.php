<?php
namespace Koselig\Proxy;

use Illuminate\Support\Str;
use WP_Comment;
use WP_Post;
use WP_Query;

/**
 * Proxies the {@link \WP_Query} class from Wordpress for a more elegant syntax.
 *
 * @property array $query Query vars set by the user
 * @property array $queryVars Query vars, after parsing
 * @property \WP_Tax_Query $taxQuery Taxonomy query
 * @property \WP_Meta_Query $metaQuery Metadata query container
 * @property \WP_Date_Query $dateQuery Date query container
 * @property object|array $queriedObject Holds the data for a single object that is queried.
 * @property int $queriedObjectId The ID of the queried object.
 * @property string $request Get post database query
 * @property array $posts List of posts.
 * @property int $postCount The amount of posts for the current query.
 * @property int $currentPost Index of the current item in the loop.
 * @property bool $inTheLoop Whether the loop has started and the caller is in the loop.
 * @property WP_Post $post The current post.
 * @property array $comments The list of comments for current post.
 * @property int $commentCount The amount of comments for the posts.
 * @property int $currentComment The index of the comment in the comment loop.
 * @property int $comment Current comment ID.
 * @property int $foundPosts The amount of found posts for the current query.
 * @property int $maxNumPages The amount of pages.
 * @property int $maxNumCommentPages The amount of comment pages.
 * @method static void init Initiates object properties and sets default values.
 * @method static void parseQueryVars Reparse the query vars.
 * @method static array fillQueryVars(array $array) Fills in the query variables, which do not exist within the
 *                                                  parameter.
 * @method static void parseQuery(string|array $query) Parse a query string and set query type booleans.
 * @method static void parseTaxQuery(array $q) Parses various taxonomy related query vars.
 * @method static void set404 Sets the 404 property and saves whether query is feed.
 * @method static mixed get(string $query_var, mixed $default) Retrieve query variable.
 * @method static void set(string $query_var, mixed $default) Set query variable.
 * @method static array posts Retrieve the posts based on query variables.
 * @method static WP_Post nextPost Set up the next post and iterate current post index.
 * @method static WP_Post post Sets up the current post.
 * @method static bool hasPosts Determines whether there are more posts available in the loop.
 * @method static void rewindPosts Rewind the posts and reset post index.
 * @method static WP_Comment nextComment Iterate current comment index and return WP_Comment object.
 * @method static WP_Comment comment Sets up the current comment.
 * @method static bool hasComments Whether there are more comments available.
 * @method static void rewindComments Rewind the comments, resets the comment index and comment to first.
 * @method static array query(string $query) Sets up the WordPress query by parsing query string.
 * @method static object queriedObject Retrieve queried object.
 * @method static int queriedObjectId Retrieve ID of the current queried object.
 * @method static bool archive Is the query for an existing archive page?
 * @method static bool postTypeArchive(array|string $post_types) Is the query for an existing post type archive page?
 * @method static bool attachment(array|string $attachment) Is the query for an existing attachment page?
 * @method static bool author(array|string|integer $author) Is the query for an existing author archive page?
 * @method static bool category(array|string|integer $category) Is the query for an existing category archive page?
 * @method static bool tag(array|string|integer $tag) Is the query for an existing tag archive page?
 * @method static bool tax(string $taxonomy, integer|string|array $term) Is the query for an existing custom taxonomy
 *                                                                       archive page?
 * @method static bool date Is the query for an existing date archive?
 * @method static bool day Is the query for an existing day archive?
 * @method static bool feed(string|array $feeds) Is the query for a feed?
 * @method static bool commentFeed Is the query for a comments feed?
 * @method static bool frontPage Is the query for the front page of the site?
 * @method static bool home Is the query for the blog homepage?
 * @method static bool month Is the query for an existing month archive?
 * @method static bool page(int|string|array $page) Is the query for an existing single page?
 * @method static bool paged Is the query for paged result and not for the first page?
 * @method static bool preview Is the query for a post or page preview?
 * @method static bool robots Is the query for the robots file?
 * @method static bool search Is the query for a search?
 * @method static bool single(int|string|array $post) Is the query for an existing single post?
 * @method static bool singular(string|array $post_types) Is the query for an existing single post of any post type
 *                                                        (post, attachment, page, ...)?
 * @method static bool time Is the query for a specific time?
 * @method static bool trackback Is the query for a trackback endpoint call?
 * @method static bool year Is the query for an existing year archive?
 * @method static bool is404 Is the query a 404 (returns no results)?
 * @method static bool embed Is the query for an embedded post?
 * @method static bool mainQuery Is the query the main query?
 * @method static true setupPostdata(WP_Post|object|int $post) Set up global post data.
 * @method static void resetPostdata After looping through a query, restores the $post global to the current post in
 *                                   this obj.
 * @method static mixed lazyloadTermMeta(mixed $check, integer $term_id) Lazyload term meta for posts in the loop.
 * @method static mixed lazyloadCommentMeta(mixed $check, integer $comment_id) Lazyload comment meta for comments in
 *                                                                             the loop.
 *
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

    /**
     * Get a property from {@link WP_Query}.
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->query->{Str::snake($name)};
    }

    /**
     * Pass a call to this function to {@link WP_Query}.
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $name = Str::snake($name);
        $name = str_replace('has', 'have', $name);

        if (!method_exists($this->query, $name)) {
            // try and find the method that was attempted to be called. Makes for a lot nicer code when reading over it.
            if (preg_match('/^is([0-9]+)$/', $name, $matches)) {
                $name = $matches[1];
            }

            if (preg_match('/^set([0-9]+)$/', $name, $matches)) {
                $name = 'set_' . $matches[1];
            }

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

    public static function instance(WP_Query $query)
    {
        $instance = new static;
        $instance->query = $query;

        return $instance;
    }
}
