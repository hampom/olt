<?php

use olt\App;

require "vendor/autoload.php";

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server("0.0.0.0:23", $loop);
$clients = [];

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) use(&$clients) {
    echo (new \DateTimeImmutable())->format('c') . ": " . $connection->getRemoteAddress() . PHP_EOL;
    if (count($clients) > 600) {
        $connection->end("サーバーが混雑している為接続できません。\r\n");
    }

    do {
       $id = mt_rand(1, 999);
    } while(in_array($id, $clients));

    $app = new App();
    $app->connect($id, $connection, $clients);
    $clients[spl_object_hash($app)] = $id;
});

$socket->on('error', 'printf');

$loop->run();
