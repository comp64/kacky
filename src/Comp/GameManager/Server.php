<?php
namespace Comp\GameManager;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Comp\Kacky\Model\Game;
use Comp\Kacky\Model\User;

class Server implements MessageComponentInterface, GameServer {

  /**
   * @var User[]
   */
  private $userList;

  /**
   * @var Game[]
   */
  private $gameList;

  /**
   * @var ConnectionInterface[]
   */
  private $userIndex;

  public function __construct() {
    $this->userList = [];
    $this->gameList = [];
    $this->userIndex = [];
  }

  /**
   * When a new connection is opened it will be passed to this method
   * @param  ConnectionInterface $conn The socket/connection that just connected to your application
   * @throws \Exception
   */
  function onOpen(ConnectionInterface $conn) {
    /** @noinspection PhpUndefinedFieldInspection */
    $conn_id = $conn->resourceId;
    $this->userList[$conn_id] = new User($conn);
  }

  /**
   * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
   * @param  ConnectionInterface $conn The socket/connection that is closing/closed
   * @throws \Exception
   */
  function onClose(ConnectionInterface $conn) {
    /** @noinspection PhpUndefinedFieldInspection */
    $conn_id = $conn->resourceId;

    $user_id = $this->userList[$conn_id]->getId();
    if ($user_id !== null) {
      unset($this->userIndex[$user_id]);
    }
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
      $message = new Message('');
      $message->decode($msg);

      /** @noinspection PhpUndefinedFieldInspection */
      $resourceId = $from->resourceId;
      $this->processMessage($this->userList[$resourceId], $message->getCmd(), $message->getArgs());
    } catch(\Exception $e) {
      $from->send(Message::error($e->getMessage()));
    }
  }

  private function isAuthenticated(User $user) {
    return $user->getId() !== null;
  }

  private function gameJoin(User $user, Game $game) {
    if ($game->userCount() > \Comp\Kacky\Game::P_MAX) {
      throw new \Exception('Too many players');
    }

    $user->setGameId($game->getId());
    $game->addUserId($user->getId(), $user->getName());
  }

  /**
   * @param User $user
   * @param string $cmd
   * @param array $args
   */
  private function processMessage(User $user, string $cmd, array $args) {
    try {
      switch ($cmd) {
        case 'authenticate':
          $username = $args['username'] ?? '';
          $password = $args['password'] ?? '';

          $user->loadFromDB(null, $username);
          if (($user->getId() === null) || !$user->verifyPassword($password)) {
            throw new \Exception('Invalid user or password');
          }

          // make a user_id -> socket mapping
          $this->userIndex[$user->getId()] = $user->getSocket();

          // try to find out, if the user was in game
          // put him there directly
          $previous_game = null;
          foreach ($this->gameList as $game_id => $game) {
            if (array_key_exists($user->getId(), $game->getUserIds())) {
              $user->setGameId($game_id);
              $previous_game = $game_id;
              break;
            }
          }
          if ($previous_game !== null) {
            $user->send(new Message('ok', ['text'=>'Authenticated in game ' . $previous_game, 'game_id'=>$previous_game]));
          } else {
            $user->send(Message::ok('Authenticated'));
          }

          break;

        case 'gameList':
          if (!$this->isAuthenticated($user)) {
            throw new NotLoggedInException();
          }

          $game_list = [];
          //$old_game_ids = [];
          foreach ($this->gameList as $game_id => $game) {
            if (($game->getActive() == 0) || ($user->getGameId() == $game_id)) {
              $game_list[$game_id] = [
                'title' => $game->getTitle(),
                'players' => [],
                'active' => ($game->getActive() ? ($game->getActive() > 1 ? 'ukončená' : 'prebieha') : 'pripravená')
              ];
            }
          }

          foreach ($this->userList as $some_user) {
            $some_game_id = $some_user->getGameId();
            if ($some_game_id === null) continue;
            if (!array_key_exists($some_game_id, $game_list)) continue;
            $game_list[$some_game_id]['players'][] = $some_user->getName();
          }

          $user->send(new Message('ok', ['gameList' => $game_list]));
          break;

        case 'gameNew':
          if (!$this->isAuthenticated($user)) {
            throw new NotLoggedInException();
          }

          if (!array_key_exists('title', $args)) {
            throw new \Exception('Game title required');
          }

          $max_key = 0;
          foreach ($this->gameList as $key => $value) {
            if ($key > $max_key) {
              $max_key = $key;
            }
          }
          $new_id = $max_key + 1;
          $new_game = new Game($args['title']);
          $new_game->setId($new_id);
          $new_game->setGame(new \Comp\Kacky\Game());
          $this->gameList[$new_id] = $new_game;

          $this->gameJoin($user, $new_game);

          $user->send(new Message('ok', ['text' => 'Game created and joined', 'game_id' => $new_id]));
          break;

        case 'gameJoin':
          if (!$this->isAuthenticated($user)) {
            throw new NotLoggedInException();
          }

          if (!array_key_exists('game_id', $args)) {
            throw new \Exception('Game id required');
          }

          if (!array_key_exists($args['game_id'], $this->gameList)) {
            throw new \Exception('Game id invalid');
          }

          $joining_game = $this->gameList[$args['game_id']];
          $this->gameJoin($user, $joining_game);

          $user->send(Message::ok('Joined game ' . $args['game_id']));
          break;

        case 'gameLeave':
          if (!$this->isAuthenticated($user)) {
            throw new NotLoggedInException();
          }

          if (!array_key_exists('game_id', $args)) {
            throw new \Exception('Game id required');
          }

          if (!array_key_exists($args['game_id'], $this->gameList)) {
            throw new \Exception('Game id invalid');
          }

          $leaving_game = $this->gameList[$args['game_id']];
          if (!array_key_exists($user->getId(), $leaving_game->getUserIds())) {
            throw new \Exception('Not in game');
          }

          $user->setGameId(null);
          $leaving_game->removeUserId($user->getId());

          $user->send(Message::ok('Left game ' . $args['game_id']));
          break;

        // all other messages are forwarded to the game itself
        default:
          if (!$this->isAuthenticated($user)) {
            throw new NotLoggedInException();
          }

          if ($user->getGameId() === null) {
            throw new \Exception('Not in game');
          }

          $in_game = $this->gameList[$user->getGameId()];
          $in_game->getGame()->processMessage($user, $cmd, $args, $this);

          break;
      }
    } catch (\Exception $e) {
      $user->send(Message::error($e->getMessage()));
    }
  }

  public function send(ObjectWithId $user, string $msg) {
    if (array_key_exists($user->getId(), $this->userIndex)) {
      $this->userIndex[$user->getId()]->send($msg);
    }
  }

  /**
   * @param ObjectWithId[] $users
   * @param string $msg
   */
  public function sendMany(array $users, string $msg) {
    foreach($users as $user) {
      $this->send($user, $msg);
    }
  }
}