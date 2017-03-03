<?php
namespace Comp\Kacky;

class Queue {
	private $items;
	
	function __construct() {
		$this->items = array();
	}
	
	/**
	 * removes the object at the bottom of this queue and returns that object as the value of this function.
	 * 
	 * @return object
	 */
	public function get() {
		return array_shift ( $this->items );
	}

	/**
	 * adds item to the top of this queue
	 * 
	 * @param object $item bla
	 */
	public function add($item) {
		if (is_null($item)) {
			trigger_error('Queue::add NULL input');
			die('error triggered');
		}
		array_push($this->items, $item);
	}
	
	/*
	 * function 'shuffle' shuffles the items
	 */
	public function shuffle() {
		shuffle ( $this->items );
		return $this->items;
	}
	
	/*
	 * function 'getItems' returns the items in the stack
	 */
	public function get_items() {
		return $this->items;
	}
}
?>