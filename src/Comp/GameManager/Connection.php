<?php
namespace Comp\GameManager;

use Ratchet\ConnectionInterface;
use Ratchet\Session\Serialize\PhpHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Comp\Kacky\DB;

/**
 * Class Connection
 * Wraps the ConnectionInterface which unfortunately uses magic methods and decorations.
 * Implements the getters for values that are not a part of the official interface.
 *
 * @package Comp\GameManager
 */
class Connection implements ConnectionInterface {

  /**
   * @var \Ratchet\ConnectionInterface
   */
  private $connectionInterface;

  /**
   * Connection constructor.
   * @param \Ratchet\ConnectionInterface $connectionInterface
   */
  public function __construct(ConnectionInterface $connectionInterface) {
    $this->connectionInterface = $connectionInterface;
  }

  function send($data) {
    $this->connectionInterface->send($data);
  }

  function close() {
    $this->connectionInterface->close();
  }

  /**
   * Get the unique connection ID
   * @return int
   */
  public function getId() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->connectionInterface->resourceId;
  }

  /**
   * Get the Session data
   * @param array $config
   * @return array
   */
  public function getSession(array $config) {
    /** @noinspection PhpUndefinedFieldInspection */
    $session_id = $this->connectionInterface->WebSocket->request->getCookie($config['session_name']);
    if ($session_id === null) {
      return [];
    }

    $handler = new PdoSessionHandler(DB::getPDO($config), ['db_table' => $config['session_table']]);
    $handler->open('', $config['session_name']);
    $rawData = $handler->read($session_id);
    $handler->close();

    $handler = new PhpHandler();
    return $handler->unserialize($rawData)['_sf2_attributes'];
  }
}