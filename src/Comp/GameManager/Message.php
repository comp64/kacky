<?php
namespace Comp\GameManager;

class Message {

  /**
   * @var string
   */
  private $cmd;
  /**
   * @var array
   */
  private $args;

  /**
   * Message constructor.
   * @param string $cmd
   * @param array $args
   */
  public function __construct(string $cmd=null, array $args=null) {
    $this->cmd = $cmd;
    $this->args = $args;
  }

  /**
   * @param string $msg
   * @throws \Exception
   */
  public function decode(string $msg) {
    // decode message
    $message = json_decode($msg, true);

    // validate message - must be JSON
    if (json_last_error() != JSON_ERROR_NONE) {
      throw new \Exception('Not a JSON message');
    }

    // validate message - must be array with at least 'cmd' key
    if (!is_array($message)) {
      throw new \Exception('Not an array');
    }
    if (!array_key_exists('cmd', $message)) {
      throw new \Exception('No cmd key found');
    }

    $this->cmd = substr($message['cmd'], 0, 64);

    if (!array_key_exists('args', $message)) {
      $message['args'] = [];
    }

    if (!is_array($message['args'])) {
      $message['args'] = [$message['args']];
    }

    $this->args = $message['args'];
  }

  /**
   * @return string
   */
  public function getCmd() {
    return $this->cmd;
  }

  /**
   * @return array
   */
  public function getArgs() {
    return $this->args;
  }

  /**
   * @return string
   */
  public function encode(): string {
    return json_encode([
      'cmd' => $this->cmd,
      'args' => $this->args
    ]);
  }

  /**
   * @param string $error_msg
   * @return string
   */
  public static function error(string $error_msg): string {
    $msg = new static('error', ['text' => $error_msg]);
    return $msg->encode();
  }

  /**
   * @param string $ok_msg
   * @return string
   */
  public static function ok(string $ok_msg): string {
    $msg = new static('ok', ['text' => $ok_msg]);
    return $msg->encode();
  }

  public function __toString() {
    return $this->encode();
  }
}