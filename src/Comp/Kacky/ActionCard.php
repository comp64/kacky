<?php
namespace Comp\Kacky;

class ActionCard {
  /**
   * @var int
   */
	private $id;
  /**
   * @var string
   */
	private $name;
  /**
   * @var string
   */
	private $description;
  /**
   * @var int
   */
	private $param_class;
	
	const DIVOKEJ_BILL = 0;
	const DVOJITA_HROZBA = 1;
	const DVOJITA_TREFA = 2;
	const KACHNI_TANEC = 3;
	const TURBOKACHNA = 4;
	const ZIVY_STIT = 5;
	const ROSAMBO = 6;
	const STRILEJ_VLEVO = 7;
	const STRILEJ_VPRAVO = 8;
	const JEJDA_VEDLE = 9;
	const KACHNI_UNIK = 10;
	const LEHARO = 11;
	const CHVATAM = 12;
	const KACHNI_POCHOD = 13;
	const ZAMIRIT = 14;
	const VYSTRELIT = 15;

  // skupiny kariet
  const CLASS_ZERO = 0; // Da sa zahrat vzdy, nepotrebuje cielovu kartu
  const CLASS_SINGLE = 1; // Vyzaduje 1 cielovu kartu
  const CLASS_DOUBLE = 2; // Vyzaduje 1 cielovu a 1 pomocnu kartu
  const CLASS_SPECIAL = 6; // Specialna karta s vyssim poctom parametrov (ROSAMBO)
  
	/**
	 * Constructs the instance of the action card based.
	 * 
	 * @param int $id of the action card
	 */
	function __construct($id) {
		if ($id < 0 || $id > 15) {
			return;
		}
		$this->id = $id;
		
		switch ($id) {
			case ActionCard::DIVOKEJ_BILL:
				$this->name = 'Divokej Bill';
				$this->description = 'Zastřelte libovolnou kachnu v řadě bez ohledu na to, zda je zaměřená.';
				$this->param_class = self::CLASS_SINGLE;
				break;
			case ActionCard::DVOJITA_HROZBA:
				$this->name = 'Dvojita hrozba';
				$this->description = 'Umístěte dvě karty zaměřovačů nad dvě sousedící pole v řadě.';
				$this->param_class = self::CLASS_SINGLE;
				break;
			case ActionCard::DVOJITA_TREFA:
				$this->name = 'Dvojita trefa';
				$this->description = 'Zastřelte dvě zaměřené kachny v řadě za sebou. Karty zaměřovačů z těchto polí odstraňte.';
				$this->param_class = self::CLASS_SINGLE;
				break;
			case ActionCard::KACHNI_TANEC:
				$this->name = 'Kachní tanec';
				$this->description = 'Zamíchejte všechny karty v řadě do balíčku kachen a vyložte zleva doprava nových šest karet do řady.';
				$this->param_class = self::CLASS_ZERO;
				break;
			case ActionCard::TURBOKACHNA:
				$this->name = 'Turbokachna';
				$this->description = 'Posuňte jednu svoji kachnu na pole na začátku řady.';
				$this->param_class = self::CLASS_SINGLE;
				break;
			case ActionCard::ZIVY_STIT:
				$this->name = 'Živý štít';
				$this->description = 'Skryjte svoji kachnu pod kachnu soupeře, která je v řadě přímo před ní nebo za ní.';
				$this->param_class = self::CLASS_DOUBLE;
				break;
			case ActionCard::ROSAMBO:
				$this->name = 'Rošambo';
				$this->description = 'Změňte libovolně pozice všech šesti karet v řadě.';
				$this->param_class = self::CLASS_SPECIAL;
				break;
			case ActionCard::STRILEJ_VLEVO:
				$this->name = 'Střílej vlevo!';
				$this->description = 'Posuňte libovolnou kartu zaměřovače o jedno pole doleva.';
				$this->param_class = self::CLASS_SINGLE;
				break;
			case ActionCard::STRILEJ_VPRAVO:
				$this->name = 'Střílej vpravo';
				$this->description = 'Posuňte libovolnou kartu zaměřovače o jedno pole doprava.';
				$this->param_class = self::CLASS_SINGLE;
				break;
			case ActionCard::JEJDA_VEDLE:
				$this->name = 'Jejda, vedle!';
				$this->description = 'Zastřelte libovolnou kachnu v řadě před nebo za zaměřeným polem. Kartu zaměřovače z tohoto pole odstraňte.';
				$this->param_class = self::CLASS_DOUBLE;
				break;
			case ActionCard::KACHNI_UNIK:
				$this->name = 'Kachní únik';
				$this->description = 'Zakryjte jednu libovolnou kachnu v řadě na jedno kolo hry touto kartou.';
				$this->param_class = self::CLASS_SINGLE;
				break;
			case ActionCard::LEHARO:
				$this->name = 'Leháro';
				$this->description = 'Vyměňte v řadě jednu svoji kachnu s kartou přímo za ní.';
				$this->param_class = self::CLASS_SINGLE;
				break;
			case ActionCard::CHVATAM:
				$this->name = 'Chvátám';
				$this->description = 'Vyměňte v řadě jednu svoji kachnu s kartou přímo před ní.';
				$this->param_class = self::CLASS_SINGLE;
				break;
			case ActionCard::KACHNI_POCHOD:
				$this->name = 'Kachní pochod';
				$this->description = 'Posuňte všechny karty v řadě o jedno pole dopředu. První kartu v řadě vraťte zpět do balíčku kachen.';
				$this->param_class = self::CLASS_ZERO;
				break;
			case ActionCard::ZAMIRIT:
				$this->name = 'Zamířit';
				$this->description = 'Umístěte kartu zaměřovače nad jednu libovolnou kartu oblohy.';
				$this->param_class = self::CLASS_SINGLE;
				break;
			case ActionCard::VYSTRELIT:
				$this->name = 'Vystřelit';
				$this->description = 'Zastřelte jednu zaměřenou kachnu. Kartu zaměřovače z tohoto pole odstraňte.';
				$this->param_class = self::CLASS_SINGLE;
				break;
		}
	}
	
	/**
	 * Returns the id of this card.
	 * 
	 * @return int $id of the card
	 */
	public function get_id() {
		return $this->id;
	}
	
	/**
	 * Returns the name of the action card.
	 * 
	 * @return string $name of the action card
	 */
	public function get_name() {
		return $this->name;
	}

  /**
   * @return int
   */
	public function getClass() {
	  return $this->param_class;
  }

	/**
	 * Returns the description of the action card.
	 * 
	 * @return string $description of the action card
	 */
	public function get_description() {
		return $this->description;
	}

	public function __toString() {
    return sprintf("ActionCard: [id: %d, name: %s]\n", $this->id, $this->name);
  }
}