<?php
class Player {
	private $color;
	private $name;
	private $hand;
	private $lives;
	const COLOR_WATER = - 1;
	const COLOR_VIOLET = 0;
	const COLOR_GREEN = 1;
	const COLOR_BLUE = 2;
	const COLOR_ORANGE = 3;
	const COLOR_YELLOW = 4;
	const COLOR_PINK = 5;
	
	/**
	 *
	 * @param String $name        	
	 * @param Intger $color        	
	 */
	function __construct($name, $color) {
		if ($color < 0 || $color > 5) {
			trigger_error ( 'Wrong color for the player.' );
			return;
		}
		
		$this->name = $name;
		$this->color = $color;
		$this->lives = 5;
		$this->hand = array ();
	}
	
	/*
	 * function 'getName' returns the name of the player
	 */
	public function get_name() {
		return $this->name;
	}
	
	/*
	 * function 'getColor' returns the numeric value of the color of the player
	 */
	public function get_color() {
		return $this->color;
	}
	
	/*
	 * function 'getHand' returns the hand of the player
	 */
	public function get_hand() {
		return $this->hand;
	}
	
	public function get_lives() {
		return $this->lives;
	}
	
	public function decrease_lives() {
		$this->lives --;
	}
	
	/*
	 * function 'addCardToHand' adds a card from a pile to the player's hand
	 */
	public function add_card_to_hand($actionCard) {
		if (count ( $this->hand ) == 3) {
			return trigger_error ( 'Player has already 3 card in the hand' );
		}
		
		$this->hand [] = $actionCard;
	}
	
	/*
	 * function 'removeCardFromHand' removes the card from player's hand
	 */
	public function remove_card_from_hand($idx) {
		$card = array_splice ( $this->hand, $idx, 1 ) [0];
		
		if (count ( $this->hand ) != 2) {
			trigger_error ( 'Function removeCardFromHand failed' );
			return false;
		}
		
		return $card;
	}
}
?>
