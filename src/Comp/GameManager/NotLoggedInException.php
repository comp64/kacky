<?php
namespace Comp\GameManager;

class NotLoggedInException extends \Exception {

  /**
   * NotLoggedInException constructor.
   */
  public function __construct() {
    parent::__construct('Not logged in');
  }
}