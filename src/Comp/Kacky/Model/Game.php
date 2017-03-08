<?php
// Not used at this time
namespace Comp\Kacky\Model;
use Comp\Kacky\DB;
use Comp\Kacky\Game as Game_kacky;

class Game {

  /**
   * @var int
   */
  private $id;

  /**
   * @var string
   */
  private $data;

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
   * @var \Comp\Kacky\Game
   */
  private $game;

  /**
   * Game constructor.
   * @param string $title
   */
  public function __construct(string $title) {
    $this->id = null;
    $this->game = null;
    $this->active = 0;
    $this->title = $title;
    $this->timestamp = time();
  }

  /**
   * @param int $id Game id
   * @return boolean
   */
  public function loadFromDB(int $id):boolean {
    $row = DB::getInstance()->getrow(
      "SELECT g_data, g_active, g_title, g_ts
      FROM game_kacky
      WHERE g_id=?",
      ['i', $id]
    );

    if ($row !== null) {
      $this->id = $id;
      $this->data = $row['g_data'];
      $this->title = $row['g_title'];
      $this->active = $row['g_active'];
      $this->timestamp = $row['g_ts'];
      $this->game = null;

      return true;
    } else {
      $this->id = null;
      return false;
    }
  }

  public function saveToDB() {
    $db = DB::getInstance();

    if ($this->game !== null) {
      $this->data = base64_encode(serialize($this->game));
    }

    if ($this->id === null) {
      $db->q(
        "INSERT INTO game_kacky
        SET g_data=?, g_active=?, g_title=?, g_ts=NOW()",
        ['sis', $this->data, $this->active, $this->title]
      );

      $this->id = $db->insert_id;
    } else {
      $db->q(
        "UPDATE game_kacky
        SET g_data=?, g_active=?, g_title=?
        WHERE g_id=?",
        ['sisi', $this->data, $this->active, $this->title, $this->id]
      );
    }
  }

  /**
   * @return array
   */
  public static function loadAllFromDB(): array {
    $res = DB::getInstance()->q(
      "SELECT g_id, g_data, g_active, g_title, g_ts
      FROM game_kacky"
    );
    $data = [];
    while($row = $res->fetch_assoc()) {
      $data[$row['g_id']] = $row;
    }
    $res->close();
    return $data;
  }

  /**
   * @return int
   */
  public function getId(): int {
    return $this->id;
  }

  public function setId(int $id) {
    $this->id = $id;
  }

  /**
   * @return \Comp\Kacky\Game
   */
  public function getGame(): Game_kacky {
    if ($this->game === null) {
      if ($this->data === null) {
        return null;
      }
      $this->game = unserialize(base64_decode($this->data));
    }
    return $this->game;
  }

  /**
   * @param \Comp\Kacky\Game $game
   */
  public function setGame(Game_kacky $game) {
    $this->game = $game;
  }

  /**
   * @return string
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle(string $title) {
    $this->title = $title;
  }

  /**
   * @return int
   */
  public function getActive(): int {
    return $this->active;
  }

  /**
   * @param int $active
   */
  public function setActive(int $active) {
    $this->active = $active;
  }

  /**
   * @return int
   */
  public function getTimestamp(): int {
    return $this->timestamp;
  }

  /**
   * @param int $timestamp
   */
  public function setTimestamp(int $timestamp) {
    $this->timestamp = $timestamp;
  }

  /**
   * @return array
   */
  public function getUserIds(): array {
    if ($this->game === null) return [];

    return $this->game->getWaitingUsers();
  }

  public function addUserId(int $user_id, string $name) {
    $this->game->addWaitingUser($user_id, $name);
  }

  public function removeUserId(int $user_id) {
    $this->game->removeWaitingUser($user_id);
  }

  public function userCount(): int {
    return count($this->getUserIds());
  }
}