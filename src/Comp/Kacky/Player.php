<?php
namespace Comp\Kacky;

use Comp\Kacky\Model\User;

class Player {
	private $color;
	private $id;
	private $name;

	/**
   * @var array[ActionCard]
   */
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
	 * @param int $id
	 * @param string $name
	 * @param int $color
	 */
	function __construct(int $id, string $name, int $color) {
	  $this->id = $id;
	  $this->name = $name;
	  $this->color = $color;
		$this->lives = 5;
		$this->hand = [];
	}
	
	/*
	 * function 'getColor' returns the numeric value of the color of the player
	 */
	public function getColor() {
		return $this->color;
	}

  public function getName() {
	  return $this->name;
  }

  public function getId() {
	  return $this->id;
  }

	/**
	 * function 'getHand' returns the hand of the player
   * @return array[ActionCard]
	 */
	public function getHand() {
		return $this->hand;
	}
	
	public function getLives() {
		return $this->lives;
	}
	
	public function decreaseLives() {
		$this->lives --;
	}

	/**
	 * function 'addCardToHand' adds a card from a pile to the player's hand
   * @param ActionCard $actionCard
	 */
	public function add_card_to_hand($actionCard) {
		if (count ( $this->hand ) == 3) {
			trigger_error ( 'Player has already 3 card in the hand' );
			return;
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