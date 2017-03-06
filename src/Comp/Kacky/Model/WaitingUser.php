<?php
namespace Comp\Kacky\Model;

use Comp\GameManager\ObjectWithId;

class WaitingUser implements ObjectWithId {

  /**
   * @var int
   */
  private $id;

  /**
   * @var string
   */
  private $name;

  /**
   * @var int
   */
  private $color;

  /**
   * WaitingUser constructor.
   * @param int $id
   * @param string $name
   */
  public function __construct($id, $name) {
    $this->id = $id;
    $this->name = $name;
    $this->color = -1;
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

  /**
   * @return int
   */
  public function getColor() {
    return $this->color;
  }

  /**
   * @param int $color
   */
  public function setColor(int $color) {
    $this->color = $color;
  }
}