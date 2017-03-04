<?php
// Not in use currently
namespace Comp\GameManager;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class DB {

  private static $instance;

  /**
   * @return EntityManager
   */
  public static function getInstance() {

    if (static::$instance === null) {
      $isDevMode = false;
      $config = Setup::createAnnotationMetadataConfiguration([__DIR__.'/../Kacky/Model'], $isDevMode);

      $conn = ['driver' => 'pdo_mysql',
        'user' => 'games',
        'password' => '8HFVQVcKKBJzZXQN',
        'dbname' => 'games'
      ];

      static::$instance = EntityManager::create($conn, $config);
    }

    return static::$instance;
  }

  // restrict these operations to enforce the singleton design pattern
  private function __construct() {}
  private function __wakeup() {}
  private function __clone () {}

}