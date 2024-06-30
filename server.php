<?php

declare(strict_types=1);

use Olt\Connection\ConsoleConnection;
use Olt\Connection\User;
use Olt\Connection\WebConnection;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Voryx\WebSocketMiddleware\WebSocketConnection;
use Voryx\WebSocketMiddleware\WebSocketMiddleware;

require "vendor/autoload.php";

$clients = [];

$socket = new SocketServer("0.0.0.0:23");

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) use (&$clients) {
    do {
        $id = random_int(1, 999);
    } while (in_array($id, $clients, true));
    $clients[] = $id;

    $user = new User($id);
    $userConnection = new ConsoleConnection($user, $connection);
    printf(
        "[%s]  IN: %s (%s)\n",
        (new DateTimeImmutable())->format('c'),
        spl_object_hash($user),
        $connection->getRemoteAddress()
    );

    $userConnection->connect();

    // opening
    $userConnection->boot();
});

$socket->on('error', 'var_export');

$loop = Loop::get();

$ws = new WebSocketMiddleware(
    ['/ws'],
    function (
        WebSocketConnection $connection,
    ) use (
        $loop,
        &$clients
    ) {
        do {
            $id = random_int(1, 999);
        } while (in_array($id, $clients, true));
        $clients[] = $id;

        $user = new User($id);
        $userConnection = new WebConnection($user, $connection);

        $loop->addTimer(0, function () use ($userConnection) {
            $userConnection->connect();

            // opening
            $userConnection->boot();
        });

        $connection->on(
            'error',
            function (Throwable $e) {
                var_export($e->getMessage());
            }
        );
    }
);

$frontend = file_get_contents(__DIR__ . '/web_view.html');
$server = new HttpServer(
    $ws,
    function () use ($frontend) {
        return new Response(200, [], $frontend);
    }
);

$httpSocket = new SocketServer('0.0.0.0:8080');
$server->listen($httpSocket);
