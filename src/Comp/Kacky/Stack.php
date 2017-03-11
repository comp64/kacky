<?php
namespace Comp\Kacky;

class Stack {
	private $items;
	
	/*
	 * creates the instance of a stack from array
	 */
	function __construct() {
		$this->items = [];
	}
	
	/*
	 * function 'isEmpty' tests if stack is empty
	 */
	public function is_empty() {
		return empty($this->items);
	}
	
	/*
	 * function 'pop' removes the object at the top of this stack and returns that object as the value of this function.
	 */
	public function pop() {
		return array_pop($this->items);
	}
	
	/*
	 * function 'pop' removes the object at the top of this stack and returns that object as the value of this function.
	 */
	public function peek() {
		if ($this->is_empty()) {
			return false;
		} else {
			return $this->items[count($this->items)-1];
		}
	}
	
	/*
	 * function 'push' pushes an item onto the top of this stack.
	 */
	public function push($item) {
		array_push($this->items, $item);
	}
	
	/*
	 * function 'shuffle' shuffles the items
	 */
	public function shuffle() {
		shuffle($this->items);
		return $this->items;
	}
	
	/*
	 * function 'getItems' returns the items in the stack
	 */
	public function get_items() {
		return $this->items;
	}
	
	public function set_items($items) {
		$this->items = $items;
	}

	public function __toString() {
    return sprintf("Stack: [items:\n%s]\n", implode("", $this->items));
  }
}
?>