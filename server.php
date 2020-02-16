<?php declare(strict_types=1);

use olt\ConsoleApp;
use olt\WebApp;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use React\Http\Response;
use React\Http\Server;
use React\EventLoop\Factory;
use React\Stream\ThroughStream;
use Voryx\WebSocketMiddleware\WebSocketConnection;
use Voryx\WebSocketMiddleware\WebSocketMiddleware;

require "vendor/autoload.php";

$loop = Factory::create();
$clients = [];

$socket = new React\Socket\Server("0.0.0.0:23", $loop);

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) use(&$clients) {
    if (count($clients) > 600) {
        $connection->end("サーバーが混雑している為接続できません。\r\n");
    }

    do {
       $id = mt_rand(1, 999);
    } while(in_array($id, $clients));

    $app = new ConsoleApp();
    printf(
        "[%s]  IN: %s (%s)\n",
        (new DateTimeImmutable())->format('c'),
        spl_object_hash($app),
        $connection->getRemoteAddress()
    );
    $app->connect($id, $connection);
    $clients[$id] = &$app;

    // opening
    $app->welcomeWorld();
    $app->onEncodeSetting();

});

$socket->on('error', 'var_export');

$frontend = file_get_contents(__DIR__ . '/web_view.html');

$ws = new WebSocketMiddleware(['/ws'], function (WebSocketConnection $conn, RequestInterface $request, ResponseInterface $response) use ($loop, &$clients)
{
    do {
        $id = mt_rand(1, 999);
    } while(in_array($id, $clients));

    $app = new WebApp();
    $app->connect($id, $conn);
    $clients[$id] = &$app;

    $loop->addTimer(0, function () use ($app) {
        // opening
        $app->welcomeWorld();
        $app->subTitle();
        $app->onNickName();
    });

    $conn->on('error', function (Throwable $e) { var_export($e->getMessage()); });
});

$server = new Server([
    $ws,
    function (RequestInterface $request, callable $next) {
        $request = $request->withHeader('Request-Time', time());
        return $next($request);
    },
    function (RequestInterface $request) use(&$clients, $frontend) {
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

        return new Response(200, [], $frontend);
    }
]);

$httpSocket = new React\Socket\Server(8080, $loop);
$server->listen($httpSocket);

$loop->run();
