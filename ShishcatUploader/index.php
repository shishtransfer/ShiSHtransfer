<?php

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/utils/uploadReqBody.php";
require __DIR__ . "/utils/previousReadDiscardHolder.php";
require __DIR__ . "/utils/client.php";
require __DIR__ . "/utils/GarbageCollector.php";
require __DIR__ . "/utils/RangesHandler.php";
require __DIR__ . "/API/dl.php";
require __DIR__ . "/API/stats.php";
require __DIR__ . "/API/delete.php";
require __DIR__ . "/API/registered_ws.php";
require __DIR__ . "/API/upload.php";

use \Amp\ByteStream\ResourceOutputStream;
use \Amp\Http\Server\Router;
use \Amp\Http\Server\Server;
use \Amp\Log\ConsoleFormatter;
use \Amp\Log\StreamHandler;
use \Amp\Socket;
use Amp\Redis\Config;
use Amp\Redis\Redis;
use Amp\Redis\RemoteExecutor;
use Monolog\Logger;

\Amp\Loop::run(function () {
    $servers = [
        Socket\listen("localhost:".$_SERVER["argv"][1])
    ];
    $logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter);
    $logger = new Logger('server');
    $logger->pushHandler($logHandler);

    $router = new Router;
    $conf_ = json_decode(file_get_contents(__DIR__."/../config.json"),true);
    $config = \Amp\Mysql\ConnectionConfig::fromString(
        $conf_["mysql_string"]
    );
    $urls = $conf_["MAIN"];
    foreach ($urls as $users=>$port){
        $urls[$users] = "http://localhost:$port";
    }
    $client = Amp\Http\Client\HttpClientBuilder::buildDefault();
    $redis = new Redis(new RemoteExecutor(Config::fromUri('redis://')));
    $sclient = new \ShishTransfer\Utils\Client($client,$urls);
    $db = \Amp\Mysql\pool($config);
    $router->addRoute('GET', '/', new Amp\Http\Server\RequestHandler\CallableRequestHandler(function ($req) {
        $rac = $req->getHeader("Accept-Language");
        if($rac === null) $rac = "en-EN";
        $za = explode("-",$rac);
        if($za[0] == "it") return new \Amp\Http\Server\Response(302, ['Location' => '/it.html']);
        return new \Amp\Http\Server\Response(302, ['Location' => '/en.html']);
    }));
    $router->addRoute('POST', '/upload', new \ShishTransfer\API\Upload($sclient,$db,$redis));
    $router->addRoute('GET', '/f/{id}', new \ShishTransfer\API\Download($sclient,$db,$redis,$logger));
    $router->addRoute('GET', '/delete/{hash}', new \ShishTransfer\API\Delete($sclient,$db,$redis));
    $router->addRoute('GET', '/ref', new \Amp\Websocket\Server\Websocket($ws = new \ShishTransfer\API\RefsWS($sclient,$db,$redis)));
    $router->addRoute('GET', '/getRef/{refid}', new \ShishTransfer\API\GetReference($sclient,$db,$redis,$ws));
    $router->addRoute('GET', '/delRef/{refid}', new \ShishTransfer\API\DelReference($sclient,$db,$redis,$ws));
    $router->addRoute('POST', '/addRef', new \ShishTransfer\API\AddReference($sclient,$db,$redis,$ws));
    $router->addRoute('GET', '/stats', new \ShishTransfer\API\Stats($sclient,$db,$redis));
    \ShishTransfer\Utils\GarbageCollector::start();
    $router->setFallback(new \Amp\Http\Server\StaticContent\DocumentRoot(__DIR__."/site"));
    $server = new Server($servers, $router, $logger, 
        (new Amp\Http\Server\Options)
        ->withoutCompression()
        ->withoutHttp2Upgrade()
        ->withHttp1Timeout(99999999999999)
        ->withDebugMode()
        ->withAllowedMethods(['GET', 'POST', 'HEAD'])
        ->withConnectionsPerIpLimit(10000)
        ->withChunkSize(409600)
    );
    yield $server->start();
    \Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
        \Amp\Loop::cancel($watcherId);
        yield $server->stop();
    });
});
