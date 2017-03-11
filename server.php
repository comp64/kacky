<?php
use Comp\Kacky\DB;
use Comp\GameManager\Server;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Session\SessionProvider;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

require __DIR__ . '/vendor/autoload.php';

$server = IoServer::factory(
  new HttpServer(
    new WsServer(
      new SessionProvider(
        new Server(),
        new PdoSessionHandler(DB::getPDO(), ['db_table'=>'session']),
        ['name'=>'kacky_wi']
      )
    )
  ), 8080
);

$server->run();