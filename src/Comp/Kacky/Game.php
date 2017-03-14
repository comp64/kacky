<?php
namespace Comp\Kacky;

use Comp\GameManager\Message;
use Comp\GameManager\GameServer;
use Comp\GameManager\ObjectWithId;

class Game {

  /**
   * @var int
   */
  private $id;

  /**
   * @var string
   */
  private $title;

  /**
   * @var int
   */
  private $active;

  /**
   * @var int
   */
  private $timestamp;

  /**
   * players in game
   * @var Player[]
   */
	private $players;

	/**
   * game table
   * @var Table
   */
	private $table;

	// player, whose turn it is
	private $activePlayer;

	// is game over?
	private $gameOver;

	/**
	 * @var string[]
	 */
	private $chatMessages;

	const P_MIN = 2;
	const P_MAX = 6;
	
	/**
	 * Constructs the instance of game.
   * @param string $title
	 */
	function __construct(string $title) {
    $this->active = 0;
    $this->title = $title;
    $this->timestamp = time();
		$this->players = [];
		$this->gameOver = false;
		$this->chatMessages = [];
  }

  public function start() {
    $this->gameOver = false; // needs to be here in case of re-start
    foreach($this->players as $player) {
      $player->reset();
    }

    $nOP = count($this->players);

    if ($nOP < Game::P_MIN || $nOP > Game::P_MAX) {
      throw new \Exception('Incorrect number of players');
    }

    // initialize private properties
    $this->table = new Table($this->players);

    // shuffle player starting positions
    shuffle($this->players);
    $this->activePlayer = 0;

    // shuffle deck
    $this->table->get_card_pile()->shuffle();

    // deal hands
    foreach ($this->players as $player) {
      for($i = 0; $i < 3; $i ++) {
        $player->add_card_to_hand($this->table->get_card_pile()->pop());
      }
    }

    // shuffle ducks
    $this->table->get_ducks_in_deck()->shuffle();

    // deal ducks
    $this->table->deal_ducks();
    $this->active = 1;
  }

	/**
	 * Sets next player to move.
	 */
	private function move_active_player() {
		$this->activePlayer = ($this->activePlayer + 1) % count ( $this->players );
	}

	private function get_player_id_by_user_id($user_id) {
	  foreach($this->players as $id => $player) {
	    if ($user_id == $player->getId()) {
	      return $id;
      }
    }
    return false;
  }

	public function is_gameover() {
		return $this->gameOver;
	}

	private function get_player_name_by_river_pos($pos) {
		$duck = $this->table->get_ducks_on_board()[$pos];
		if ($duck->get_features() == Duck::DUCK)
			$color = $duck->get_card()->get_color();
		else
			$color = $duck->get_color();
		
		if ($color == - 1) {
      return 'voda';
    }
		
		foreach ( $this->players as $id => $player ) {
			if ($color == $player->getColor() ) {
        return $player->getName();
      }
		}
		return '';
	}
	
	/**
	 * Returns whether the player can play the action card.
	 *
	 * @param Player $player
	 *        	playing the action card
	 * @param ActionCard $cardInHand
	 *        	to be played
	 * @param array $params
	 *        	holds the information about the target duck and action card parameters needed to play
   * @return bool
	 */
	private function validate($player, $cardInHand, $params) {
		$myColor = $player->getColor();
		// first card on the table
		$cardTable = $params[0];
		// secondary parameter for JEJDA_VEDLE, ZIVY_STIT and ROSAMBO
		$secondCard = $params[1];
		
		switch ($cardInHand->get_id()) {
			// tieto karty neocakavaju ziaden parameter, takze sme sa dohodli, ze je mozne ich zahrat na lubovolnu kartu
			case ActionCard::ROSAMBO :
				break;
			
			case ActionCard::DIVOKEJ_BILL :
				break;
			
			case ActionCard::KACHNI_TANEC :
				break;
			
			case ActionCard::KACHNI_POCHOD :
				break;
			
			case ActionCard::DVOJITA_HROZBA :
				// Checks if the duck is not last in the row
				if ($cardTable == Table::VISIBLE_DUCKS - 1) {
					return false;
				}
				
				// Checks if either selected duck or the one on the right is targeted
				if ($this->table->is_targeted ( $cardTable ) || $this->table->is_targeted ( $cardTable + 1 )) {
					return false;
				}
				
				break;
			
			case ActionCard::DVOJITA_TREFA :
				// Checks if the duck is not last in the row
				if ($cardTable == Table::VISIBLE_DUCKS - 1)
					return false;
					// Checks if selected duck and one on the right is not targeted
				if (! $this->table->is_targeted ( $cardTable ) || ! $this->table->is_targeted ( $cardTable + 1 )) {
					return false;
				}
				break;
			
			case ActionCard::TURBOKACHNA :
				// Checks if selected duck is the same color as player
				if (! $this->is_mine ( $myColor, $cardTable )) {
					return false;
				}
				
				break;
			
			case ActionCard::STRILEJ_VLEVO :
				// Checks if selected duck is not tageted or has left neighbour targeted
				if ($cardTable == 0) {
					return false;
				}
				
				if ($this->table->is_targeted ( $cardTable - 1 ) || ! $this->table->is_targeted ( $cardTable )) {
					return false;
				}
				
				break;
			
			case ActionCard::STRILEJ_VPRAVO :
				// Checks if selected duck is not tageted or has right neighbour targeted
				if ($cardTable == Table::VISIBLE_DUCKS - 1) {
					return false;
				}
				
				if ($this->table->is_targeted ( $cardTable + 1 ) || ! $this->table->is_targeted ( $cardTable )) {
					return false;
				}
				
				break;
			
			case ActionCard::KACHNI_UNIK :
				// Checks if selected duck is water
				if ($this->is_mine ( Player::COLOR_WATER, $cardTable )) {
					return false;
				}
				
				$river_card = $this->table->get_ducks_on_board () [$cardTable];
				if (! is_null ( $river_card->get_card () ) && get_class ( $river_card->get_card () ) === 'Comp\Kacky\ActionCard') {
					return false;
				}
				
				break;
			
			case ActionCard::LEHARO :
				// Checks if selected duck has not the player's color nor right neighbour
				if ($cardTable == Table::VISIBLE_DUCKS - 1) {
					return false;
				}
				
				if (! $this->is_mine ( $myColor, $cardTable )) {
					return false;
				}
				
				break;
			
			case ActionCard::CHVATAM :
				// Checks if selected duck has not the player's color nor right neighbour
				if ($cardTable == 0) {
					return false;
				}
				
				if (! $this->is_mine ( $myColor, $cardTable )) {
					return false;
				}
				
				break;
			
			case ActionCard::ZAMIRIT :
				// Checks if selected duck is targeted
				if ($this->table->is_targeted ( $cardTable )) {
					return false;
				}
				
				break;
			
			case ActionCard::VYSTRELIT :
				// Checks if selected duck is not targeted
				if (! $this->table->is_targeted ( $cardTable )) {
					return false;
				}
				
				break;
			
			case ActionCard::JEJDA_VEDLE :
				if (is_null ( $secondCard )) {
					if (! $this->table->is_targeted ( $cardTable )) {
						return false;
					}
				} else {
					
					if (abs ( $cardTable - $secondCard ) != 1) {
						return false;
					}
				}
				
				break;
			
			case ActionCard::ZIVY_STIT :
				if (! $this->is_mine ( $myColor, $cardTable )) {
					return false;
				}
				
				if (! is_null ( $this->table->get_ducks_on_board () [$cardTable]->get_card () )) {
					return false;
				}
			
				if ($secondCard === null) {
					// If selected first, check right neighbour if WATER or same color or duck has duck on it
					if ($cardTable == 0) {
						if ($this->is_mine ( Player::COLOR_WATER, $cardTable + 1 ) || $this->is_mine ( $myColor, $cardTable + 1 ) || $this->is_duck_on_duck($cardTable + 1)) {
							return false;
						}
						// If selected last, check left neighbour if WATER or same color or duck has duck on it
					} elseif ($cardTable == Table::VISIBLE_DUCKS - 1) {
						if ($this->is_mine ( Player::COLOR_WATER, $cardTable - 1 ) || $this->is_mine ( $myColor, $cardTable - 1 ) || $this->is_duck_on_duck($cardTable - 1)) {
							return false;
						}
						// If selected middle, check right and left neighbour if WATER or same color or duck has duck on it
					} else {
						if (($this->is_mine ( Player::COLOR_WATER, $cardTable - 1 ) || $this->is_mine ( $myColor, $cardTable - 1 )  || $this->is_duck_on_duck($cardTable - 1)) && ($this->is_mine ( Player::COLOR_WATER, $cardTable + 1 ) || $this->is_mine ( $myColor, $cardTable + 1 ) || $this->is_duck_on_duck($cardTable + 1))) {
							return false;
						}
					}
				} elseif (abs ( $cardTable - $secondCard ) != 1 || $this->is_mine ( $myColor, $secondCard ) || $this->is_mine ( Player::COLOR_WATER, $secondCard )) {
					return false;
				} elseif ($this->is_duck_on_duck($secondCard)) {
					return false;
				}
				
				break;
		}
		
		return true;
	}
	
	private function is_duck_on_duck($idx) {
	  $var = $this->table->get_ducks_on_board () [$idx]->get_card ();
	  if (!is_null($var) && (get_class($var) === 'Comp\Kacky\Duck')) {
  	  return true;
	  }
	  return false;
	}
 	
	/**
	 * Returns whether the duck on the board is player's.
	 *
	 * @param int $playerColor
	 *        	color of the duck on the table
	 * @param int $idx
	 *        	of the duck on the table
   * @return bool
	 */
	private function is_mine($playerColor, $idx) {
		$card = $this->table->get_ducks_on_board () [$idx];
		
		if ($playerColor == $card->get_color ()) {
			return true;
		}
		
		if (get_class ( $card->get_card () ) === 'Comp\Kacky\Duck' && $card->get_card ()->get_color () == $playerColor) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Returns the possible moves to the player.
	 *
	 * @param int $player_id
	 *        	on turn
	 * @return array $moves possible moves
	 */
	private function get_possible_moves($player_id) {
		$player = $this->players[$player_id];

		$master = [];
		$moves = [];
		$is = false;
		
		foreach ($player->getHand() as $k => $cardInHand) {
			$moves[$k] = [];
			
			for ($i = 0; $i < Table::VISIBLE_DUCKS; $i ++) {
				$params = [
					0 => $i,
					1 => null
				];
				
				$is |= ($val = $this->validate($player, $cardInHand, $params));
				$moves[$k][$i] = $val;
			}
			$moves[$k][Table::PILE] = false;
		}
		
		if (!$is) {
			foreach ($moves as $k => $v) {
				$moves[$k][Table::PILE] = true;
			}
		}
		
		$master[0] = $moves;
		
		$moves = [];

		foreach ($player->getHand() as $k => $cardInHand) {
			if ($player->getHand()[$k]->get_id() == ActionCard::JEJDA_VEDLE ||
          $player->getHand()[$k]->get_id() == ActionCard::ZIVY_STIT) {
				$moves[$k] = [];
				
				for ($i = 0; $i < Table::VISIBLE_DUCKS; $i ++) {
					$moves[$k][$i] = [];
					
					for ($j = 0; $j < Table::VISIBLE_DUCKS; $j ++) {
						$params = [
							0 => $i,
							1 => $j
						];
						
						$val = $this->validate($player, $cardInHand, $params);
						$moves[$k][$i][$j] = $val;
					}
				}
			} elseif ($player->getHand()[$k]->get_id() == ActionCard::ROSAMBO) {
				$moves[$k] = true;
			} else {
				$moves[$k] = false;
			}
		}
		
		$master[1] = $moves;
		
		return $master;
	}
	
	/**
	 *
	 * @param int $playerId
	 * @param int $cardInHandId
	 */
	private function use_card($playerId, $cardInHandId) {
		$this->table->get_card_trash ()->push ( $this->players [$playerId]->remove_card_from_hand ( $cardInHandId ) );
		$this->players [$playerId]->add_card_to_hand ( $this->table->get_card_pile ()->pop () );
	}

  private function could_be_played($playerId, $cardInHandId, $param) {
    $moves = $this->get_possible_moves($playerId);

    return $moves[0][$cardInHandId][$param[0]];
  }

	/**
	 *
	 * @param int $playerId
	 * @param int $cardInHandId
	 * @param array $param
   * @return bool|array
	 */
	public function play($playerId, $cardInHandId, $param) {
		$player = $this->players[$playerId];
		$cardInHand = $player->getHand()[$cardInHandId];
		
		// if active player is not on the turn
		if ($this->activePlayer != $playerId) {
			return false;
		}

		// generated messages
		$messages = [];

		// died ducks (to include in action message)
		$died_ducks = [];

		// if pile is empty, shuffle the trash and put it into the pile, empty the trash
		if ($this->table->get_card_pile ()->is_empty ()) {
			$this->table->get_card_pile ()->set_items ( $this->table->get_card_trash ()->shuffle () );
			$this->table->set_card_trash ();
		}

    if (!$this->could_be_played($playerId, $cardInHandId, $param)) {
		  return false;
		}

		if ($param [0] == 6) {
			$this->use_card ( $playerId, $cardInHandId );
			// message template
			$play_message = [
			  sprintf('%s zahadzuje %s', $player->getName(), mb_strtoupper( $cardInHand->get_name(), 'UTF-8' ))
			];
		} else {
			// message template that fits all CLASS_SINGLE cards
			$play_message = [
			  sprintf('%s - %s', $player->getName(), mb_strtoupper( $cardInHand->get_name (), 'UTF-8' )),
        sprintf(' na %d (%s)', $param [0] + 1, $this->get_player_name_by_river_pos( $param[0] ))
			];
			
			// possible moves
			switch ($cardInHand->get_id ()) {
				case ActionCard::ROSAMBO :
					// validate input parameters - need array
					if (! is_array ( $param [1] ) || count ( $param [1] ) != 6) {
						trigger_error ( 'ROSAMBO error: bad input' );
						return false;
					}
					
					$this->table->reorder_ducks ( $param [1] );
					
					$play_message [1] = '';
					break;
				
				case ActionCard::DIVOKEJ_BILL :
					// validate input parameters - no need
					if ($this->table->get_ducks_on_board () [$param [0]]->get_color () != Player::COLOR_WATER) {
						$color = $this->table->remove_duck ( $param [0], true );
						// not tried to kill KACHNI_UNIK
						if ($color != Player::COLOR_WATER)
							$died_ducks [] = $param [0];
					} else {
					  $color = Player::COLOR_WATER;
          }
					$this->table->set_target ( $param [0], false );
					
					if (count ( $this->table->get_ducks_on_board () ) != 6) {
						$this->table->put_duck_on_board ();
					}
					
					// decrease lives
					foreach ( $this->players as $player ) {
						if ($player->getColor () == $color) {
							$player->decreaseLives ();
						}
					}
					
					break;
				
				case ActionCard::KACHNI_TANEC :
					// validate input parameters - no need
					$this->table->collect_ducks ();
					$this->table->get_ducks_in_deck ()->shuffle ();
					$this->table->deal_ducks ();
					
					$play_message [1] = '';
					break;
				
				case ActionCard::KACHNI_POCHOD :
					// validate input parameters - no need
					
					for($i = 0; $i < Table::VISIBLE_DUCKS - 1; $i ++) {
						$this->table->swap_ducks ( $i, $i + 1 );
					}
					
					$this->table->remove_duck ( Table::VISIBLE_DUCKS - 1, false );
					$this->table->put_duck_on_board ();
					
					$play_message [1] = '';
					break;
				
				case ActionCard::DVOJITA_HROZBA :
					// validate input parameters - no need
					$this->table->set_target ( $param[0], true );
					$this->table->set_target ( $param[0] + 1, true );

          $play_message[1] = sprintf(' na %d a %d (%s, %s)',
            $param[0] + 1, $param[0] + 2,
            $this->get_player_name_by_river_pos( $param[0] ), $this->get_player_name_by_river_pos( $param[0] + 1 )
          );
					break;
				
				case ActionCard::DVOJITA_TREFA :
				  // Emit message describing the action
					$play_message[1] = sprintf(' na %d a %d (%s, %s)',
            $param[0] + 1, $param[0] + 2,
            $this->get_player_name_by_river_pos( $param[0] ), $this->get_player_name_by_river_pos( $param[0] + 1 )
          );

				  // kill the duck on the 2-nd position (if not water)
					if ($this->table->get_ducks_on_board()[$param[0] + 1]->get_color() != Player::COLOR_WATER) {
						$color_right = $this->table->remove_duck( $param[0] + 1, true );
						// not tried to kill KACHNI_UNIK
						if ($color_right != Player::COLOR_WATER)
							$died_ducks[] = $param[0] + 1;
					} else {
					  $color_right = Player::COLOR_WATER;
          }
					$this->table->set_target( $param[0] + 1, false );
					
					if ($this->table->get_ducks_on_board() [$param[0]]->get_color() != Player::COLOR_WATER) {
						$color_left = $this->table->remove_duck( $param[0], true );
						// not tried to kill KACHNI_UNIK
						if ($color_left != Player::COLOR_WATER)
							$died_ducks[] = $param[0];
					} else {
					  $color_left = Player::COLOR_WATER;
          }
					$this->table->set_target( $param[0], false );
					
					while( count( $this->table->get_ducks_on_board() ) < Table::VISIBLE_DUCKS ) {
						$this->table->put_duck_on_board();
					}
					
					foreach( $this->players as $player ) {
						if ($player->getColor() == $color_left) {
							$player->decreaseLives();
						}
						
						if ($player->getColor() == $color_right) {
							$player->decreaseLives();
						}
					}

					break;
				
				case ActionCard::TURBOKACHNA :
					// validate input parameters - no need
					for($i = $param[0]; $i > 0; $i --) {
						$this->table->swap_ducks( $i, $i - 1 );
					}
					break;
				
				case ActionCard::STRILEJ_VLEVO :
					// validate input parameters - no need
					$this->table->set_target( $param [0], false );
					$this->table->set_target( $param [0] - 1, true );
					break;
				
				case ActionCard::STRILEJ_VPRAVO :
					// validate input parameters - no need
					$this->table->set_target( $param [0], false );
					$this->table->set_target( $param [0] + 1, true );
					break;
				
				case ActionCard::KACHNI_UNIK :
					// validate input parameters - no need
					$ret = $this->table->put_card_on_duck( $param [0], $cardInHand, count( $this->players ) );
					if (! $ret) {
						trigger_error ( 'KACHNI_UNIK error: invalid combination of ducks' );
						return false;
					}
					$this->players [$playerId]->remove_card_from_hand ( $cardInHandId );
					$this->players [$playerId]->add_card_to_hand ( $this->table->get_card_pile ()->pop () );
					break;
				
				case ActionCard::LEHARO :
					// validate input parameters - no need
					$this->table->swap_ducks ( $param [0], $param [0] + 1 );
					break;
				
				case ActionCard::CHVATAM :
					// validate input parameters - no need
					$this->table->swap_ducks ( $param [0], $param [0] - 1 );
					break;
				
				case ActionCard::ZAMIRIT :
					// validate input parameters - no need
					$this->table->set_target ( $param [0], true );
					break;
				
				case ActionCard::VYSTRELIT :
					// validate input parameters - no need
					if ($this->table->get_ducks_on_board () [$param [0]]->get_color () != Player::COLOR_WATER) {
						$color = $this->table->remove_duck ( $param [0], true );
						// not tried to kill KACHNI_UNIK
						if ($color != Player::COLOR_WATER) {
              $died_ducks [] = $param [0];
            }
					} else {
					  $color = Player::COLOR_WATER;
          }
					$this->table->set_target ( $param [0], false );
					
					if (count ( $this->table->get_ducks_on_board () ) != 6) {
						$this->table->put_duck_on_board ();
					}
					
					foreach ( $this->players as $player ) {
						if ($player->getColor () == $color) {
							$player->decreaseLives ();
						}
					}
					
					break;
				
				case ActionCard::JEJDA_VEDLE :
					// validate input parameters - need numeric
					if (! is_numeric ( $param [1] )) {
						trigger_error ( 'JEJDA_VEDLE error: bad input' );
						return false;
					}
					
					$play_message[1] = sprintf(' na %d (%s)', $param[1] + 1, $this->get_player_name_by_river_pos( $param[1] ));
					
					if ($this->table->get_ducks_on_board () [$param [1]]->get_color () != Player::COLOR_WATER) {
						$color = $this->table->remove_duck ( $param [1], true );
						// not tried to kill KACHNI_UNIK
						if ($color != Player::COLOR_WATER) {
              $died_ducks [] = $param [1];
            }
					} else {
					  $color = Player::COLOR_WATER;
          }
					$this->table->set_target ( $param [0], false );
					
					if (count ( $this->table->get_ducks_on_board () ) != 6) {
						$this->table->put_duck_on_board ();
					}
					
					foreach ( $this->players as $player ) {
						if ($player->getColor () == $color) {
							$player->decreaseLives ();
						}
					}
					
					break;
				
				case ActionCard::ZIVY_STIT :
					// validate input parameters - need numeric
					if (! is_numeric ( $param [1] )) {
						trigger_error ( 'ZIVY_STIT error: bad input' );
						return false;
					}
					
					$na_poziciu = min ( $param [0], $param [1] );
					$play_message [1] = sprintf(' na %d (%s)', $na_poziciu + 1, $this->get_player_name_by_river_pos( $na_poziciu ));
					
					$ret = $this->table->put_card_on_duck ( $param [0], $this->table->get_ducks_on_board () [$param [1]], - 1 );
					if (! $ret) {
						trigger_error ( 'ZIVY_STIT error: invalid combination of ducks' );
						return false;
					}
					$this->table->delete_duck_from_board ( $param [1] );
					$this->table->put_duck_on_board ();
					break;
			}
			
			// in all cases (except for KACHNI_UNIK), the player's card has been used by now
			// so throw it away and get a new one
			if ($cardInHand->get_id() != ActionCard::KACHNI_UNIK)
				$this->use_card($playerId, $cardInHandId);
		}
		
		// prepare action card play messages
		$messages[] = [
			'cmd' => 100,
			'text' => '<span class="msg-sys0">' . htmlentities(implode('', $play_message)) . '</span>'
		];
		$messages[] = [
			'cmd' => 101,
			'player_id' => $playerId,
			'card_id' => $cardInHand->get_id(),
			'river_pos' => $param[0],
			'extras' => $param[1],
			'died_pos' => $died_ducks
		];
		
		$this->move_active_player();
		
		// skontrolujeme, ci niekto vyhral
		$alive = 0;
		$winner = 'voda';
		foreach ($this->players as $player) {
			if ($player->getLives() > 0) {
				$alive++;
				$winner = $player->getName();
			}
		}
		if ($alive <= 1) {
			$this->gameOver = true;
			$this->activePlayer = - 1;
			
			// game-over text message
			$messages[] = [
				'cmd' => 100,
				'text' => sprintf('<span class="msg-sys0">Game over! %s je víťaz! <a href="javascript:game_start()">*Hrať znova*</a></span>', mb_strtoupper($winner, 'UTF-8'))
			];
		}
		
		$unprotect_pos = [];
		foreach ($this->table->get_ducks_on_board() as $k => $duck) {
			$card = $duck->decrease_protection();
			
			if (is_null($card)) {
			} elseif (get_class($card) === 'Comp\Kacky\ActionCard') {
				$this->table->get_card_trash()->push($card);
				$unprotect_pos[] = $k;
			} else {
				$card = $card->decrease_protection();
				if (!is_null($card)) {
					$this->table->get_card_trash()->push($card);
					$unprotect_pos[] = $k;
				}
			}
		}
		if (count($unprotect_pos)) {
      $messages[] = [
        'cmd' => 102,
        'positions' => $unprotect_pos
      ];
    }
		
		return $messages;
	}
	
	// return the game state of the game
  // this will be parsed by UI
	public function getDetails($player_id) {
		$state = [];

		$state['title'] = $this->title;
		$state['active'] = $this->active;
		$state['players'] = [];
		foreach ($this->players as $k => $player) {
			$state['players'][$k] = [
			  'id' => $player->getId(),
				'name' => $player->getName(),
				'lives' => $player->getLives(),
				'color' => $player->getColor(),
				'current' => ($k == $player_id),
				'on_move' => ($k == $this->activePlayer)
			];
		}
		if (!$this->active) {
		  return $state;
    }
		
		$state['river'] = [];
		foreach ($this->table->get_ducks_on_board() as $k => $duck) {
			$features = $duck->get_features();
			
			$tmp = [
				'color' => $duck->get_color(),
				'target' => $this->table->is_targeted($k),
				'action_card' => false,
				'other_duck' => false,
				'features' => $features
			];
			
			switch($features) {
				case Duck::PROT :
					$tmp['action_card'] = $duck->get_card()->get_id();
					break;
				case Duck::DUCK :
					$tmp['other_duck'] = $duck->get_card()->get_color();
					break;
				case Duck::DUCK_PROT :
					$tmp['action_card'] = $duck->get_card()->get_card()->get_id();
					$tmp['other_duck'] = $duck->get_card()->get_color();
					break;
			}
			
			$state['river'][] = $tmp;
		}
		
		$state['pile'] = [
			'id' => false,
			'name' => false,
			'desc' => false
		];

		$pile_top = $this->table->get_card_trash()->peek();
		if ($pile_top !== false) {
			$state['pile'] = [
				'id' => $pile_top->get_id(),
				'name' => $pile_top->get_name(),
				'desc' => $pile_top->get_description()
			];
		}
		
		$state['hand'] = [];
		foreach ($this->players[$player_id]->getHand() as $card) {
			$state['hand'][] = [
				'id' => $card->get_id(),
				'name' => $card->get_name(),
				'desc' => $card->get_description()
			];
		}
		
		$state['moves'] = $this->get_possible_moves($player_id);
		
		return $state;
	}

	public function debug_counts() {
		$action_cards_hand = 0;
		$lives = 0;
		foreach ($this->players as $player) {
			$action_cards_hand += count ($player->getHand());
			$lives += $player->getLives();
		}
		$action_cards_pile = count($this->table->get_card_pile()->get_items());
		$action_cards_trash = count($this->table->get_card_trash()->get_items());
		
		$ducks_board = count($this->table->get_ducks_on_board());
		$ducks_deck = count($this->table->get_ducks_in_deck()->get_items());
		
		$null_count = 0;
		foreach ($this->table->get_ducks_in_deck()->get_items() as $duck) {
			if (is_null($duck)) {
				$null_count++;
			}
		}
		
		return [
			'action_cards_hand' => $action_cards_hand,
			'action_cards_pile' => $action_cards_pile,
			'action_cards_trash' => $action_cards_trash,
			'ducks_board' => $ducks_board,
			'ducks_deck' => $ducks_deck,
			'lives' => $lives,
			'null count' => $null_count
		];
	}

	public function processMessage(ObjectWithId $user, string $cmd, array $args, GameServer $ms) {
	  // bump game timestamp
    $this->timestamp = time();

	  switch($cmd) {
      case 'setColor':
        if (!array_key_exists('color', $args)) {
          throw new \Exception('Color required');
        }

        if (($args['color'] < -1) || ($args['color'] > 5)) {
          throw new \Exception('Color invalid');
        }

        // check if the color is available
        $count = 0;
        foreach($this->players as $waiting_user) {
          if ($waiting_user->getColor() == $args['color']) {
            $count++;
          }
        }
        if (($count > 0) && ($args['color'] != -1)) {
          throw new \Exception('Color in use');
        }

        $player_id = $this->get_player_id_by_user_id($user->getId());
        $this->players[$player_id]->setColor($args['color']);

        $ms->sendMany($this->players, new Message('setColor', ['userId'=>$user->getId(), 'color'=>$args['color']]));
        break;

      case 'gameDetails':
        // try obtaining the player_id. Works even if not found (game not started yet)
        $player_id = $this->get_player_id_by_user_id($user->getId());

        $ms->send($user, new Message('gameDetails', $this->getDetails($player_id)));
        break;

      case 'gameStart':
        $this->start();

        $player_id = $this->get_player_id_by_user_id($user->getId());
        if ($player_id === false) {
          throw new \Exception('Invalid user id');
        }

        $ms->sendMany($this->players, new Message('gameStart'));
        foreach($this->players as $player_num => $player) {
          $ms->send($player, new Message('gameDetails', $this->getDetails($player_num)));
        }

        break;

      case 'cardPlay':
        $player_id = $this->get_player_id_by_user_id($user->getId());
        if ($player_id === false) {
          throw new \Exception('Invalid user id');
        }

        if (!$this->active) {
          throw new \Exception('Inactive game');
        }

        if (strlen($args['param2'])) {
          $args['param1'] = array_map(function ($v) {
            return $v * 1;
          }, explode(' ', trim($args['param2'])));
        }

        $ret=$this->play($player_id, $args['card_id'], array($args['param0'], $args['param1']));

        if ($ret === false) {
          throw new \Exception('Game::play() returned false');
        }

        // put new chat messages into archive
        foreach($ret as $message) {
          if ($message['cmd'] == 100) {
            $this->chatMessages[] = $message['text'];
          }
        }

        if ($this->is_gameover()) {
          $this->active=2;
        }

        foreach($this->players as $player_num => $player) {
          $gstate = $this->getDetails($player_num);
          $gstate['messages'] = $ret;
          $ms->send($player, new Message('gameDetails', $gstate));
        }

        break;

      case 'chatLoad': // load all previous chat messages
        $ms->send($user, new Message('chatLoad', $this->chatMessages));
        break;

      case 'chat':
        if (!strlen($args['text'] ?? '')) {
          throw new \Exception('Empty message');
        }

        if ($this->active == 0) {
          throw new \Exception('Game not started');
        }

        $player = $this->players[$this->get_player_id_by_user_id($user->getId())];
        $msg = mb_substr($args['text'], 0, 512, 'UTF-8');
        $wholeMsg = sprintf('<span class="msg-col%d">%s: %s</span>', $player->getColor(), htmlentities($player->getName(), ENT_COMPAT | ENT_HTML5, 'UTF-8'), htmlentities($msg, ENT_COMPAT | ENT_HTML5, 'UTF-8'));
        $this->chatMessages[] = $wholeMsg;
        $ms->sendMany($this->players, new Message('chat', ['text'=> $wholeMsg]));
        break;

      default:
        throw new \Exception('Unhandled message in Comp\Kacky\Game');
        break;
    }
  }

  /**
   * @return Player[]
   */
  public function getWaitingUsers() {
    return $this->players;
  }

  /**
   * @param int $user_id
   * @return bool
   */
  public function hasPlayerById(int $user_id) {
    $player_id = $this->get_player_id_by_user_id($user_id);
    return ($player_id===false)?false:true;
  }

  public function addWaitingUser(int $user_id, string $name) {
    $this->players[] = new Player($user_id, $name);
  }

  public function removeWaitingUser(int $user_id) {
    $player_id = $this->get_player_id_by_user_id($user_id);
    if ($player_id !== false) {
      array_splice($this->players, $player_id, 1);
    }
  }

  public function connectionStatusChange(int $user_id, int $status, GameServer $ms) {
    $statusMap = [
      0=>'DISCONNECT: %s je OFFLINE',
      1=>'CONNECT: %s je ONLINE'
    ];
    $player_id = $this->get_player_id_by_user_id($user_id);
    if ($player_id !== false) {
      $msg = sprintf('<span class="msg-sys0">'.$statusMap[$status].'</span>', $this->players[$player_id]->getName());
      $this->chatMessages[] = $msg;
      $ms->sendMany($this->players, new Message('chat', ['text'=>$msg]));
    }
  }

  /**
   * @return int
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param int $id
   */
  public function setId(int $id) {
    $this->id = $id;
  }

  /**
   * @return int
   */
  public function getActive() {
    return $this->active;
  }

  /**
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  public function __toString() {
    return sprintf("Game: [id: %d, title: %s, active: %d, activePlayer: %d, over: %d, players:\n%s]\n", $this->id,
      $this->title, $this->active, $this->activePlayer, $this->gameOver?1:0, implode("", $this->players)
    );
  }

  /**
   * Time in seconds since last interaction with this game
   * @return int
   */
  public function getAge() {
    return time() - $this->timestamp;
  }
}