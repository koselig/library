<?php
namespace Koselig\Support;

use RecursiveIterator;

/**
 * Interface to allow easier menu item iteration.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class RecursiveMenuIterator implements RecursiveIterator
{
    public $items;
    private $current = 0;

    /**
     * Create a new RecursiveMenuIterator instance.
     *
     * @param string $menu menu to get items of
     */
    public function __construct($menu)
    {
        if (is_string($menu)) {
            $menu = wp_get_nav_menu_object(get_nav_menu_locations()[$menu]);

            // Wordpress is nice and will always place children below parents so we can easily create a tree out of it
            $items = collect(wp_get_nav_menu_items($menu))->keyBy('ID')->reverse();
            $itemsArray = $items->all();

            foreach ($itemsArray as $id => $item) {
                $itemsArray[$id]->children = $items->where('menu_item_parent', $id)->values();
            }

            // only have nodes without a parent at the top level of the tree
            $this->items = collect($itemsArray)->filter(function ($item) {
                return $item->menu_item_parent == 0;
            })->reverse()->values();
        } else {
            $this->items = $menu;
        }
    }

    /**
     * Return the current element.
     *
     * @link http://php.net/manual/en/iterator.current.php
     *
     * @return \WP_Post Can return any type.
     *
     * @since 5.0.0
     */
    public function current()
    {
        return $this->items[$this->current];
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
        return isset($this->items[$this->current]);
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
