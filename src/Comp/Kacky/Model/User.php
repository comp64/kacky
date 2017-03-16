<?php
namespace Comp\Kacky\Model;

use Comp\GameManager\Connection;
use Comp\GameManager\ObjectWithId;
use Comp\Kacky\DB;

class User implements ObjectWithId {

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
  private $passhash;

  /**
   * @var Connection
   */
  private $socket;

  /**
   * @var int
   */
  private $game_id;

  /**
   * @var string
   */
  private $foreign_id;

  /**
   * User constructor.
   * @param Connection $socket
   */
  public function __construct(Connection $socket=null) {
    $this->socket = $socket;
    $this->game_id = null;
    $this->foreign_id = null;
  }

  public function __destruct() {
    $this->socket = null;
  }

  /**
   * load from DB and verify password
   * @param string $username
   * @param string $password
   * @throws \Exception
   */
  public function verifyFromDB(string $username, string $password) {
    $this->loadFromDB(null, $username);
    if (($this->getId() === null) || !$this->verifyPassword($password)) {
      throw new \Exception('Invalid user or password');
    }
  }

  public function verifyFacebook(string $token) {
    $data = file_get_contents('https://graph.facebook.com/me?access_token='.$token);
    $user_data = json_decode($data, true);
    if (($user_data === null) || !array_key_exists('id', $user_data)) {
      throw new \Exception('Invalid Facebook access token');
    }
    $this->loadFromDB(null, null, 'F'.$user_data['id']);
    if ($this->getId() === null) {
      $username = explode(' ', $user_data['name'], 2);
      $this->name = $username[0];
      $this->foreign_id = 'F'.$user_data['id'];
      $this->passhash = '!';
      $this->saveToDB();
    }
  }

  public function verifyGoogle(string $token) {
    $data = file_get_contents('https://www.googleapis.com/oauth2/v3/tokeninfo?id_token='.$token);
    $user_data = json_decode($data, true);
    if (($user_data === null) || !array_key_exists('sub', $user_data)) {
      throw new \Exception('Invalid Google access token');
    }
    $this->loadFromDB(null, null, 'G'.$user_data['sub']);
    if ($this->getId() === null) {
      $username = $user_data['given_name'];
      $this->name = $username;
      $this->foreign_id = 'G'.$user_data['sub'];
      $this->passhash = '!';
      $this->saveToDB();
    }
  }

  /**
   * @param int $id
   * @param string $name
   * @param string $foreign_id
   * @return boolean
   */
  public function loadFromDB(int $id=null, string $name=null, string $foreign_id=null) {
    if ($id !== null) {
      $row = DB::getInstance()->getrow(
        "SELECT u_id, u_name, u_pass
      FROM `user`
      WHERE u_id=?",
        ['i', $id]
      );
    } elseif ($name !== null) {
      $row = DB::getInstance()->getrow(
        "SELECT u_id, u_name, u_pass
      FROM `user`
      WHERE u_name=?",
        ['s', $name]
      );
    } else {
      $row = DB::getInstance()->getrow(
        "SELECT u_id, u_name, u_pass
      FROM `user`
      WHERE u_foreign_id=?",
        ['s', $foreign_id]
      );
    }

    if ($row !== null) {
      $this->id = $row['u_id'];
      $this->name = $row['u_name'];
      $this->passhash = $row['u_pass'];

      return true;
    } else {
      $this->id = null;

      return false;
    }
  }

  private function saveToDB() {
    $db = DB::getInstance();
    if ($this->id === null) {
      $db->q(
        "INSERT INTO `user`
        SET u_name=?, u_pass=?, u_foreign_id=?",
        ['sss', $this->name, $this->passhash, $this->foreign_id]
      );
      $this->id = $db->insert_id;
    }
  }

  /**
   * @return int
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  public function setName(string $name) {
    $this->name = $name;
  }

  /**
   * @param string $password
   * @return bool
   */
  public function verifyPassword(string $password) {
    return sha1($this->name . ':game:' . $password) === $this->passhash;
  }

  /**
   * @return Connection
   */
  public function getSocket() {
    return $this->socket;
  }

  /**
   * @return int
   */
  public function getGameId() {
    return $this->game_id;
  }

  /**
   * @param int $game_id
   */
  public function setGameId(int $game_id=null) {
    $this->game_id = $game_id;
  }

  /**
   * @param string $msg
   */
  public function send(string $msg) {
    $this->socket->send($msg);
  }

  public function __toString() {
    return sprintf("User: [id: %d, name: %s, socketId: %d, gameId: %s]\n", $this->id, $this->name, $this->getSocket()->getId(), $this->game_id??'-');
  }
}