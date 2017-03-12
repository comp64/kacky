<?php
namespace Comp\Kacky;

/**
 * Class Stack
 * Stack and Queue functionality
 * @package Comp\Kacky
 */
class Stack {
  /**
   * @var ActionCard[]|Duck[]
   */
	private $items;
	
	function __construct() {
		$this->items = [];
	}

  /**
   * @return bool
   */
	public function is_empty() {
		return empty($this->items);
	}

  /**
   * QUEUE: removes the object at the bottom and returns it
   *
   * @return ActionCard|Duck
   */
  public function get() {
    return array_shift($this->items);
  }

  /**
   * STACK: removes the object at the top and returns it
   *
   * @return ActionCard|Duck
   */
	public function pop() {
		return array_pop($this->items);
	}
	
	/**
	 * STACK: get the object at the top, return it but do not remove it
   *
   * @return ActionCard|Duck|bool
	 */
	public function peek() {
		if ($this->is_empty()) {
			return false;
		} else {
			return $this->items[count($this->items)-1];
		}
	}

  /**
   * QUEUE: adds item to the top
   *
   * @param ActionCard|Duck $item
   */
  public function add($item) {
    array_push($this->items, $item);
  }

	/**
	 * STACK: pushes an item onto the top
   *
   * @param ActionCard|Duck $item
	 */
	public function push($item) {
		array_push($this->items, $item);
	}
	
	/**
	 * shuffles the items
   *
   * @return ActionCard[]|Duck[]
	 */
	public function shuffle() {
		shuffle($this->items);
		return $this->items;
	}
	
	/**
	 * @return ActionCard[]|Duck[]
	 */
	public function get_items() {
		return $this->items;
	}

  /**
   * @param ActionCard[]|Duck[] $items
   */
	public function set_items($items) {
		$this->items = $items;
	}

	public function __toString() {
    return sprintf("Stack: [items:\n%s]\n", implode("", $this->items));
  }
}
?>