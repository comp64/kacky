<?php
namespace Comp\Kacky;

class Duck {
  /**
   * @var int
   */
	private $color;
  /**
   * @var int
   */
	private $protection;
  /**
   * @var ActionCard|Duck
   */
	private $card;
	
	// duck features constants
	const ONLY=0;
	const PROT=1;
	const DUCK=2;
	const DUCK_PROT=3;
	
	/**
	 * constructs the instance of duck with initial values
   * @param int $color
	 */
	function __construct(int $color) {
		$this->protection = 0;
		$this->card = null;
		$this->color = $color;
	}
	
	/**
	 * Returns the numeric value of the color of this duck.
	 *
	 * @return int $color
	 */
	public function get_color() {
		return $this->color;
	}
	
	/**
	 * Sets the protection for this duck.
	 * $prot <= -1, the action card ZIVY STIT was used and this duck is invisible
	 * $prot = 0, the duck is unprotected and can be killed
	 * $prot >= 1, determines the number of protection turns
	 *
	 * @param ActionCard|Duck $card
	 *        	card to put on the current duck
	 * @param int $prot
	 *        	the length of the protection
	 */
	public function set_protection($card, $prot) {
		$this->card = $card;
		$this->protection = $prot;
	}
	
	/**
	 * function 'decreaseProtection' decreases protection by 1 for the duck and returns action card KACHNI UNIK if needed
	 *
	 * @return ActionCard|Duck $card
	 */
	public function decrease_protection() {
		if ($this->protection != 0) {
			$this->protection -= 1;
			
			if ($this->protection == 0 && get_class($this->card) === 'Comp\Kacky\ActionCard') {
				return $this->remove_card();
			} elseif (get_class($this->card) === 'Comp\Kacky\Duck') {
				return $this->card;
			}
		}
		
		return null;
	}
	
	/**
	 * Returns the card on this duck.
	 *
	 * @return ActionCard|Duck $card on this duck
	 */
	public function get_card() {
		return $this->card;
	}
	
	/**
	 * function 'removeCard' returns the action (id=10, Kachní únik) card on the duck
   * @return ActionCard|Duck
	 */
	public function remove_card() {
		$temp = $this->card;
		$this->card = null;
		
		return $temp;
	}
	
	/**
	 * @return Duck
	 */
	public function reset() {
		$this->protection = 0;
		$this->card = null;
		return $this;
	}
	
	/**
   * unified function gets the one of of the 4 possible states
   *
   * @return int
   */
	public function get_features() {
		if (is_null($this->card)) return self::ONLY;
		if (get_class($this->card) === 'Comp\Kacky\ActionCard') return self::PROT;
		if (is_null($this->card->get_card())) return self::DUCK;
		return self::DUCK_PROT;
	}

	public function __toString() {
    return sprintf("Duck: [color: %d, protection: %d, card: \n%s]\n", $this->color, $this->protection, $this->card??'-');
  }
}