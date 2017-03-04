<?php
namespace Comp\Kacky;

class Table {
	private $ducksOnBoard;
	private $ducksInDeck;
	private $targeted;
	private $cardTrash;
	private $cardPile;
	
	// constants
	const VISIBLE_DUCKS = 6;
	const PILE = self::VISIBLE_DUCKS;
	
	/**
	 * creates the instance of the board with initial values depending on the number of players
	 *
	 * @param array $players
	 *        	who participate in the game
	 */
	function __construct($players) {
		$this->cardPile = new Stack ();
		$this->cardTrash = new Stack ();
		$this->ducksInDeck = new Queue ();
		$this->ducksOnBoard = array ();
		$this->targeted = array ();
		
		// add colored ducks into the deck
		foreach ( $players as $player ) {
			for($i = 0; $i < 5; $i ++) {
				$this->ducksInDeck->add ( new Duck ( $player->get_color () ) );
			}
		}
		
		// add water to the deck
		for($i = 0; $i < 5; $i ++) {
			$this->ducksInDeck->add ( new Duck ( - 1 ) );
		}
		
		// set default targets to false
		for($i = 0; $i < 6; $i ++) {
			$this->targeted [$i] = false;
		}
		
		$allActionCards = array (
				0 => 1,
				1 => 1,
				2 => 1,
				3 => 1,
				4 => 1,
				5 => 2,
				6 => 2,
				7 => 2,
				8 => 2,
				9 => 2,
				10 => 3,
				11 => 3,
				12 => 3,
				13 => 6,
				14 => 10,
				15 => 12 
		);

		foreach ( $allActionCards as $id => $num ) {
			for($i = 0; $i < $num; $i ++) {
				$this->cardPile->push ( new ActionCard ( $id ) );
			}
		}
	}
	
	/**
	 * Returns the array of ducks on board.
	 *
	 * @return array[Duck] $ducksOnBoard array of ducks on this board
	 */
	public function get_ducks_on_board() {
		return $this->ducksOnBoard;
	}
	
	/**
	 * Adds a new duck on the board from the duck deck.
	 */
	public function put_duck_on_board() {
		/*
		 * if (count ( $this->ducksOnBoard ) != 5) {
		 * trigger_error ( 'Not 5 ducks on the board' );
		 * return false;
		 * }
		 */
		 
		$var = $this->ducksInDeck->get ();
		if (is_null($var)) {
		  $var = new Duck(Player::COLOR_WATER);
		}
	  $this->ducksOnBoard [] = $var;
	}
	
	/**
	 * Returns the ducks in the deck.
	 *
	 * @return Queue $ducksInDeck all ducks in the side deck
	 */
	public function get_ducks_in_deck() {
		return $this->ducksInDeck;
	}
	
	/**
	 * Returns the stack of cards in the pile.
	 *
	 * @return Stack $cardPile action cards in the side deck
	 */
	public function get_card_pile() {
		return $this->cardPile;
	}
	
	public function set_card_trash() {
		$this->cardTrash = new Stack ();
	}
	
	/**
	 * Returns the stack of cards in the trash.
	 *
	 * @return Stack $cardTrash the stack of already used action cards
	 */
	public function get_card_trash() {
		return $this->cardTrash;
	}
	
	/**
	 * Returns whether the position on board is targeted.
	 *
	 * @param int $idx
	 *        	the index of the position on this board
	 * @return bool true if the position on this board is targeted
	 */
	public function is_targeted($idx) {
		return $this->targeted [$idx];
	}
	
	/**
	 * Targets or detargets the place on the board.
	 *
	 * @param int $idx
	 *        	the index of the position on this board
	 * @param bool $bool
	 *        	to target or detarget this position
	 */
	public function set_target($idx, $bool) {
		$this->targeted [$idx] = $bool;
	}
	
	/**
	 * Removes the duck from the board and either places it again onto the deck or discards.
	 *
	 * @param int $idx
	 *        	the index of the position on this board
	 * @param bool $kill
	 *        	true if the duck is to killed
   * @return int
	 */
	public function remove_duck($idx, $kill) {
		if ($kill) {
			if ($this->ducksOnBoard [$idx]->get_card () === null) {
				// there is only bottom duck to kill and putting new duck on the board
				$color = $this->ducksOnBoard [$idx]->get_color ();
				array_splice ( $this->ducksOnBoard, $idx, 1 );
				return $color;
			} elseif (get_class ( $this->ducksOnBoard [$idx]->get_card () ) === 'ActionCard') {
				// do nothing
				return Player::COLOR_WATER;
			} elseif (get_class ( $this->ducksOnBoard [$idx]->get_card () ) === 'Duck') {
				if (get_class ( $this->ducksOnBoard [$idx]->get_card ()->get_card () ) === 'ActionCard') {
					// killing the bottom duck
					$color = $this->ducksOnBoard [$idx]->get_color ();
					$this->ducksOnBoard [$idx] = $this->ducksOnBoard [$idx]->get_card ();
					return $color;
				} else {
					// killing the top duck
					$color = $this->ducksOnBoard [$idx]->get_card()->get_color ();
					$this->ducksOnBoard [$idx]->remove_card ();
					return $color;
				}
			}
		} else {
			if (get_class ( $this->ducksOnBoard [$idx]->get_card () ) === 'ActionCard') {
				// removing action card on the bottom duck
				$this->cardTrash->push ( $this->ducksOnBoard [$idx]->remove_card () );
			} elseif (get_class ( $this->ducksOnBoard [$idx]->get_card () ) === 'Duck') {
				if (get_class ( $this->ducksOnBoard [$idx]->get_card ()->get_card () ) === 'ActionCard') {
					// removing action card on the top duck
					$this->cardTrash->push ( $this->ducksOnBoard [$idx]->get_card ()->remove_card () );
				}
				// removing the top duck
				$this->ducksInDeck->add ( $this->ducksOnBoard [$idx]->remove_card ()->reset () );
			}
			
			// removing the bottom duck and putting new duck on the board
			$this->ducksOnBoard [$idx]->reset ();
			$this->ducksInDeck->add ( array_splice ( $this->ducksOnBoard, $idx, 1 ) [0] );
		}

		return null;
	}
	
	/**
	 * Switches two ducks on the board.
	 *
	 * @param int $idx1
	 *        	the index of the first duck to switch
	 * @param int $idx2
	 *        	the index of the first duck to switch
	 */
	public function swap_ducks($idx1, $idx2) {
		$temp = $this->ducksOnBoard [$idx1];
		$this->ducksOnBoard [$idx1] = $this->ducksOnBoard [$idx2];
		$this->ducksOnBoard [$idx2] = $temp;
	}
	
	/**
	 * Permutates the ducks.
	 *
	 * @param array $permutation
	 *        	permutation of the ducks
	 */
	public function reorder_ducks($permutation) {
		$reorderedDucks = array ();
		
		foreach ( $permutation as $val ) {
			$reorderedDucks [] = $this->ducksOnBoard [$val];
		}

		$this->ducksOnBoard = $reorderedDucks;
	}
	
	/**
	 * Deals new six ducks on the board
	 */
	public function deal_ducks() {
		for($i = 0; $i < 6; $i ++) {
			$this->ducksOnBoard [] = $this->ducksInDeck->get ();
		}
	}
	
	/**
	 * Collects all ducks from the board into the deck
	 */
	public function collect_ducks() {
		for($i = 5; $i >= 0; $i --) {
			$this->remove_duck ( $i, false );
		}
	}
	
	/**
	 * Places a card onto the duck
	 *
	 * @param int $idx
	 *        	the index of the target duck
	 * @param ActionCard|Duck $card
	 *        	the card to be placed on the indexed duck
   * @param int $prot
   * @return bool
	 */
	public function put_card_on_duck($idx, $card, $prot) {
		if (is_null($this->ducksOnBoard [$idx]->get_card ())) {
			$this->ducksOnBoard [$idx]->set_protection ( $card, $prot );
			return true;
		} elseif (get_class ( $this->ducksOnBoard [$idx]->get_card () ) === 'Duck' && get_class ( $card ) === 'ActionCard') {
			$this->ducksOnBoard [$idx]->get_card ()->set_protection ( $card, $prot );
			return true;
		}
		
		// reached when putting duck on a duck with KACHNI_UNIK or putting a duck on a Duck_duck
		return false;
	}
	
	public function delete_duck_from_board($idx) {
		return array_splice ( $this->ducksOnBoard, $idx, 1 ) [0];
	}
}
?>