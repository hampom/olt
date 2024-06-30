<?php

declare(strict_types=1);

use Olt\Connection\ConsoleConnection;
use Olt\Connection\User;
use React\Socket\SocketServer;

require "vendor/autoload.php";

$clients = [];

$socket = new SocketServer("0.0.0.0:23");

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) use (&$clients) {
    do {
        $id = random_int(1, 999);
    } while (in_array($id, $clients, true));

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
