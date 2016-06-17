<?php

use Olt\App;

require "vendor/autoload.php";

spl_autoload_register(function($class) {
    include 'src/' . strtr($class, "\\", DIRECTORY_SEPARATOR) . '.php';
});

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);
$clients = [];

$socket->on('connection', function ($conn) use(&$clients) {

    $now = new DateTime();
    echo $now->format('c') . ": " . $conn->getRemoteAddress() . PHP_EOL;
    if (count($clients) > 600) {
        $conn->end("サーバーが混雑している為接続できません。\r\n");
    }

    $id = 0;
    do {
       $id = mt_rand(1, 999);
    } while(in_array($id, $clients));

    $app = new App();
    $app->connect($id, $conn, $clients);
    $clients[spl_object_hash($app)] = $id;
});

$socket->listen(23, "0.0.0.0");
$loop->run();
