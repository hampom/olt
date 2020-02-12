<?php

use olt\App;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Http\Server;

require "vendor/autoload.php";

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server("0.0.0.0:23", $loop);
$clients = [];

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) use(&$clients) {
    if (count($clients) > 600) {
        $connection->end("サーバーが混雑している為接続できません。\r\n");
    }

    do {
       $id = mt_rand(1, 999);
    } while(in_array($id, $clients));

    $app = new App();
    printf(
        "[%s]  IN: %s (%s)\n",
        (new \DateTimeImmutable())->format('c'),
        spl_object_hash($app),
        $connection->getRemoteAddress()
    );
    $app->connect($id, $connection);
    $clients[$id] = &$app;
});

$socket->on('error', 'printf');

$server = new Server(function (ServerRequestInterface $request) use(&$clients) {
    $response = [];
    $path = $request->getUri()->getPath();

    if ($path == '/api/users') {
        foreach ($clients as $id => $app) {
            $response[] = [
                'id' => $id,
                'name' => $app->nickName,
                'channel' => $app->channel
            ];
        }
        return new Response(
            200,
            array(
                'Content-Type' => 'application/json'
            ),
            json_encode($response)
        );
    }

    return new Response(400);
});

$httpSocket = new React\Socket\Server(8080, $loop);
$server->listen($httpSocket);

$loop->run();
