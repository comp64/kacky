<?php
namespace Comp\GameManager;

use Ratchet\ConnectionInterface;
use Symfony\Component\HttpFoundation\Session\Session;

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
   * Get the Session object
   * @return Session
   */
  public function getSession() {
    /** @noinspection PhpUndefinedFieldInspection */
    return $this->connectionInterface->Session;
  }
}