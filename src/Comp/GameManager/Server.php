<?php
namespace Comp\GameManager;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Comp\Kacky\Model\Game;
use Comp\Kacky\Model\User;

class Server implements MessageComponentInterface {

  private $userList;
  private $gameList;

  public function __construct() {
    $this->userList = [];
    $this->gameList = [];
  }

  /**
   * When a new connection is opened it will be passed to this method
   * @param  ConnectionInterface $conn The socket/connection that just connected to your application
   * @throws \Exception
   */
  function onOpen(ConnectionInterface $conn) {
    $conn_id = $conn->resourceId;
    $this->userList[$conn_id] = new User($conn);
  }

  /**
   * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
   * @param  ConnectionInterface $conn The socket/connection that is closing/closed
   * @throws \Exception
   */
  function onClose(ConnectionInterface $conn) {
    $conn_id = $conn->resourceId;
    unset($this->userList[$conn_id]);
  }

  /**
   * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
   * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
   * @param  ConnectionInterface $conn
   * @param  \Exception $e
   * @throws \Exception
   */
  function onError(ConnectionInterface $conn, \Exception $e) {
    $conn->close();
  }

  /**
   * Triggered when a client sends data through the socket
   * @param  \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
   * @param  string $msg The message received
   * @throws \Exception
   */
  function onMessage(ConnectionInterface $from, $msg) {
    try {
      $message = new Message();
      $message->decode($msg);

      $this->processMessage($this->userList[$from->resourceId], $message->getCmd(), $message->getArgs());
    } catch(\Exception $e) {
      $from->send(Message::error($e->getMessage()));
    }
  }

  private function isAuthenticated(User $user) {
    return $user->getId() !== null;
  }

  /**
   * @param User $user
   * @param string $cmd
   * @param array $args
   * @throws \Exception
   */
  private function processMessage(User $user, string $cmd, array $args) {
    switch($cmd) {
      case 'authenticate':
        $username = $args['username'] ?? '';
        $password = $args['password'] ?? '';

        $user->loadFromDB(null, $username);
        if (($user->getId() === null) || !$user->verifyPassword($password)) {
          $user->send(Message::error('Invalid user or password'));
        } else {
          $user->send(Message::ok('Authenticated'));
        }

        break;

      case 'gameList':
        if ($this->isAuthenticated($user)) {
          $game_list = [];
          $old_game_ids = [];
          foreach($this->gameList as $game_id => $game) {
            if (($game->getActive() == 0) || ($user->getGameId() == $game_id)) {
              $game_list[$game_id] = [
                'title' => $game->getTitle(),
                'players' => [],
                'active' => ($game->getActive()?($game->getActive()>1?'ukončená':'prebieha'):'pripravená')
              ];
            }
          }

          foreach($this->userList as $some_user) {
            $some_game_id = $some_user->getGameId();
            if ($some_game_id === null) continue;
            if (!array_key_exists($some_game_id, $game_list)) continue;
            $game_list[$some_game_id]['players'][] = $some_user->getName();
          }

          $user->send(new Message('gameList', $game_list));
        } else {
          $user->send(Message::error('Not logged in'));
        }
        break;

      case 'gameNew':
        if ($this->isAuthenticated($user)) {
          $keys = array_keys($this->gameList);
          $max_key = array_pop($keys);
          $new_id = $max_key + 1;
          if (array_key_exists($new_id, $this->gameList)) {
            throw new \Exception('Create game failed - id already in use');
          }
          $this->gameList[$new_id] = new Game();
        } else {
          $user->send(Message::error('Not logged in'));
        }
        break;
    }
  }
}