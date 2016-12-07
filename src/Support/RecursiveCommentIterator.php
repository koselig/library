<?php
namespace Koselig\Support;

use Illuminate\Support\Collection;
use Koselig\Models\Comment;
use RecursiveIterator;

/**
 * Interface to allow easier looping over comments.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class RecursiveCommentIterator implements RecursiveIterator
{
    private $current = 0;
    private $comments;

    /**
     * Create a new RecursiveCommentIterator instance.
     *
     * @param Collection|Comment[] $comments
     */
    public function __construct($comments)
    {
        $this->comments = $comments;
    }

    /**
     * Return the current element.
     *
     * @link http://php.net/manual/en/iterator.current.php
     *
     * @return Comment Can return any type.
     *
     * @since 5.0.0
     */
    public function current()
    {
        return $this->comments[$this->current];
    }

    /**
     * Move forward to next element.
     *
     * @link http://php.net/manual/en/iterator.next.php
     *
     * @return void Any returned value is ignored.
     *
     * @since 5.0.0
     */
    public function next()
    {
        $this->current++;
    }

    /**
     * Return the key of the current element.
     *
     * @link http://php.net/manual/en/iterator.key.php
     *
     * @return mixed scalar on success, or null on failure.
     *
     * @since 5.0.0
     */
    public function key()
    {
        return $this->current;
    }

    /**
     * Checks if current position is valid.
     *
     * @link http://php.net/manual/en/iterator.valid.php
     *
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     *
     * @since 5.0.0
     */
    public function valid()
    {
        return isset($this->comments[$this->current]);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     *
     * @return void Any returned value is ignored.
     *
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->current = 0;
    }

    /**
     * Returns if an iterator can be created for the current entry.
     *
     * @link http://php.net/manual/en/recursiveiterator.haschildren.php
     *
     * @return bool true if the current entry can be iterated over, otherwise returns false.
     *
     * @since 5.1.0
     */
    public function hasChildren()
    {
        return !$this->current()->children->isEmpty();
    }

    /**
     * Returns an iterator for the current entry.
     *
     * @link http://php.net/manual/en/recursiveiterator.getchildren.php
     *
     * @return RecursiveIterator An iterator for the current entry.
     *
     * @since 5.1.0
     */
    public function getChildren()
    {
        return new static($this->current()->children);
    }
}
