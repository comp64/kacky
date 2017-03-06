<?php
namespace Comp\GameManager;

interface GameServer {
  public function send(ObjectWithId $user, string $msg);

  /**
   * @param ObjectWithId[] $users
   * @param string $msg
   */
  public function sendMany(array $users, string $msg);

  //public function setActive
}