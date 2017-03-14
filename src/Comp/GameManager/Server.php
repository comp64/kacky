<?php
namespace Comp\GameManager;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Comp\Kacky\Game;
use Comp\Kacky\Player;
use Comp\Kacky\Model\User;
use Comp\Kacky\DB;

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
   * @var array
   */
  private $config;

  public function __construct() {
    $this->userList = [];
    $this->gameList = [];
    $this->config = DB::getConfig();
  }

  /**
   * When a new connection is opened it will be passed to this method
   * @param  ConnectionInterface $conn The socket/connection that just connected to your application
   * @throws \Exception
   */
  function onOpen(ConnectionInterface $conn) {
    // Wrap the Connection immediately to get access to the magic fields
    $conn = new Connection($conn);
    $this->userList[$conn->getId()] = new User($conn);
  }

  /**
   * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
   * @param  ConnectionInterface $conn The socket/connection that is closing/closed
   * @throws \Exception
   */
  function onClose(ConnectionInterface $conn) {
    $conn = new Connection($conn);
    $conn_id = $conn->getId();
    $user = $this->userList[$conn_id];

    foreach($this->gameList as $game) {
      if ($game->hasPlayerById($user->getId())) {
        $game->connectionStatusChange($user->getId(), 0, $this);
      }
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

      $from = new Connection($from);
      $resourceId = $from->getId();
      $this->processMessage($this->userList[$resourceId], $message->getCmd(), $message->getArgs());
    } catch(\Exception $e) {
      $from->send(Message::error($e->getMessage()));
    }
  }

  private function isAuthenticated(User $user) {
    return $user->getId() !== null;
  }

  private function purgeOldGames() {
    // maximum allowed age in particular game activity states
    $ageMap = [
      0 => 1800,
      1 => 7200,
      2 => 7200
    ];
    $oldGames = [];
    foreach($this->gameList as $game_id => $game) {
      if ($game->getAge() > $ageMap[$game->getActive()]) {
        // GC
        $oldGames[] = $game_id;
      }
    }

    foreach($oldGames as $game_id) {
      foreach($this->userList as $user) {
        if ($user->getGameId() === $game_id) {
          $user->setGameId(null);
        }
      }

      $this->sendMany($this->gameList[$game_id]->getWaitingUsers(), new Message('gameClosed', ['gameId'=>$game_id]));
      unset($this->gameList[$game_id]);
    }
  }

  /**
   * @param User $user
   * @return array
   */
  private function gameListing(User $user) {
    $game_list = [];
    foreach ($this->gameList as $game_id => $game) {
      if (($game->getActive() == 0) || ($game->hasPlayerById($user->getId()))) {
        $game_list[$game_id] = [
          'title' => $game->getTitle(),
          'players' => array_map(function(Player $x) {
            return $x->getName();
          }, $game->getWaitingUsers()),
          'active' => ($game->getActive() ? ($game->getActive() > 1 ? 'ukončená' : 'prebieha') : 'pripravená')
        ];
      }
    }

    return $game_list;
  }

  private function gameJoin(User $user, Game $game) {
    if ($game->getActive() && !$game->hasPlayerById($user->getId())) {
      throw new \Exception('Game already started');
    }

    if (count($game->getWaitingUsers()) > Game::P_MAX) {
      throw new \Exception('Too many players');
    }

    $user->setGameId($game->getId());

    if (!$game->hasPlayerById($user->getId())) {
      $game->addWaitingUser($user->getId(), $user->getName());
      return true;
    } else {
      return false;
    }
  }

  /**
   * @param User $user
   * @param string $cmd
   * @param array $args
   * @throws \Exception
   * @throws NotLoggedInException
   */
  private function processMessage(User $user, string $cmd, array $args) {
    try {
      switch ($cmd) {
        case 'authenticate':
          if (!array_key_exists('username', $args)) {
            // try session authentication instead
            $session = $user->getSocket()->getSession($this->config);
            $logged = $session['isLogged'] ?? false;
            $userId = $session['userId'] ?? 0;
            if ($logged) {
              $user->loadFromDB($userId);
            } else {
              throw new \Exception('Session not authenticated');
            }
          } else {
            // go for a traditional user/pass auth
            $username = $args['username'] ?? '';
            $password = $args['password'] ?? '';

            $user->verifyFromDB($username, $password);
          }

          $preferredGameId = $args['gameId'] ?? 0;
          if (array_key_exists($preferredGameId, $this->gameList)) {
            $preferredGame = $this->gameList[$preferredGameId];
            if ($preferredGame->hasPlayerById($user->getId())) {
              $user->setGameId($preferredGameId);
            }
          }

          // notify all concerned games about connected user
          foreach($this->gameList as $game) {
            if ($game->hasPlayerById($user->getId())) {
              $game->connectionStatusChange($user->getId(), 1, $this);
            }
          }

          $user->send(Message::ok('Authenticated'));

          break;

        case 'gameList':
          if (!$this->isAuthenticated($user)) {
            throw new NotLoggedInException();
          }

          $this->purgeOldGames();
          $user->send(new Message('gameList', $this->gameListing($user)));
          break;

        case 'gameNew':
          if (!$this->isAuthenticated($user)) {
            throw new NotLoggedInException();
          }

//          if ($user->getGameId() !== null) {
//            throw new \Exception('Already in another game');
//          }

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
          $this->gameList[$new_id] = $new_game;

          $this->gameJoin($user, $new_game);

          $user->send(new Message('gameNew', ['gameId' => $new_id]));
          $this->sendNonPlaing(null, function(User $x){
            return new Message('gameList', $this->gameListing($x));
          });
          break;

        case 'gameJoin':
          if (!$this->isAuthenticated($user)) {
            throw new NotLoggedInException();
          }

          if (!array_key_exists('gameId', $args)) {
            throw new \Exception('Game id required');
          }

//          if (($user->getGameId() !== null) && ($user->getGameId() != $args['gameId'])) {
//            throw new \Exception('Already in another game');
//          }

          if (!array_key_exists($args['gameId'], $this->gameList)) {
            throw new \Exception('Game id invalid');
          }

          $joined_game = $this->gameList[$args['gameId']];
          $ret = $this->gameJoin($user, $joined_game);

          if ($ret) {
            $this->sendMany($joined_game->getWaitingUsers(),
              new Message('gameJoin', ['userId' => $user->getId(), 'userName' => $user->getName()])
            );
          }
          break;

        case 'gameLeave':
          if (!$this->isAuthenticated($user)) {
            throw new NotLoggedInException();
          }

          if ($user->getGameId() === null) {
            throw new \Exception('Not in game');
          }

          if (array_key_exists($user->getGameId(), $this->gameList)) {
            $leaving_game = $this->gameList[$user->getGameId()];
            if ($leaving_game->getActive()) {
              throw new \Exception('Game already started');
            }

            $leaving_game->removeWaitingUser($user->getId());
            $this->sendMany($leaving_game->getWaitingUsers(),
              new Message('gameLeave', ['userId'=>$user->getId(), 'userName'=>$user->getName()])
            );
          }
          $user->setGameId(null);
          break;

        case 'debug':
          echo "UserList: [\n".implode("", $this->userList)."]\n";
          echo "GameList: [\n".implode("", $this->gameList)."]\n";
          echo "\n";
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
          $in_game->processMessage($user, $cmd, $args, $this);

          break;
      }
    } catch (\Exception $e) {
      $user->send(Message::error($e->getMessage()));
    }
  }

  public function send(ObjectWithId $user, string $msg) {
    foreach($this->userList as $connected_user) {
      if ($connected_user->getId() == $user->getId()) {
        $connected_user->send($msg);
      }
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

  /**
   * send message to users not yet in a game
   * @param Message $msg
   * @param callable $msg_fn
   */
  private function sendNonPlaing(Message $msg=null, callable $msg_fn=null) {
    foreach($this->userList as $idle_user) {
      if ($idle_user->getGameId() === null) {
        if ($msg !== null) {
          $idle_user->send($msg);
        } else {
          $idle_user->send($msg_fn($idle_user));
        }
      }
    }
  }
}