<?php
namespace Comp\Kacky;

use Comp\GameManager\ObjectWithId;

class Player implements ObjectWithId {
  /**
   * @var int
   */
  private $id;
  /**
   * @var string
   */
  private $name;
  /**
   * @var int
   */
  private $color;

	/**
   * @var ActionCard[]
   */
	private $hand;
  /**
   * @var int
   */
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
	 */
	function __construct(int $id, string $name) {
	  $this->id = $id;
	  $this->name = $name;
	  $this->color = self::COLOR_WATER;
		$this->lives = 5;
		$this->hand = [];
	}
	
	/**
	 * function 'getColor' returns the numeric value of the color of the player
   * @return int
	 */
	public function getColor() {
		return $this->color;
	}

  /**
   * @param int $color
   */
  public function setColor(int $color) {
    $this->color = $color;
  }

  /**
   * @return string
   */
  public function getName() {
	  return $this->name;
  }

  /**
   * @return int
   */
  public function getId() {
	  return $this->id;
  }

	/**
	 * function 'getHand' returns the hand of the player
   * @return ActionCard[]
	 */
	public function getHand() {
		return $this->hand;
	}

  /**
   * @return int
   */
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
	
	/**
	 * function 'removeCardFromHand' removes the card from player's hand
   * @param int $idx
   * @return ActionCard
   * @throws \Exception
	 */
	public function remove_card_from_hand($idx) {
		$card = array_splice($this->hand, $idx, 1)[0];
		
		if (count ($this->hand) != 2) {
			throw new \Exception('Function removeCardFromHand failed');
		}
		
		return $card;
	}

	public function __toString() {
    return sprintf("Player: [id: %d, name: %s, color: %d, lives: %d, hand:\n%s]\n", $this->id, $this->name, $this->color, $this->lives, implode("", $this->hand));
  }
}