<?php
require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/config.php";
require __DIR__ . "/Backups.php";
require __DIR__ . "/API/download.php";
require __DIR__ . "/API/searchDMS.php";
require __DIR__ . "/API/upload.php";
require __DIR__ . "/API/delete.php";
use \Amp\ByteStream\ResourceOutputStream;
use \Amp\Http\Server\Router;
use \Amp\Http\Server\Server;
use \Amp\Log\ConsoleFormatter;
use \Amp\Log\StreamHandler;
use \Amp\Socket;
use \Monolog\Logger;

$MadelineProto = new \danog\MadelineProto\API(__DIR__."/sessions/$argv[1].madeline",[
    'updates' => [
        'run_callback' => false,
        'handle_updates' => false,
    ],
    'logger' => [
        'logger' => \danog\MadelineProto\Logger::ECHO_LOGGER,
        'logger_level' => \danog\MadelineProto\Logger::WARNING,
    ],
    'connection_settings'=>[
        'all'=>[
            "proxy"=>"\Socket",
            "proxy_extra"=>[
            ]
        ]
    ]
]);
$MadelineProto->async(true);

$conf = new \ConfigParser($argv[1]);
\Amp\Loop::run(function () use ($MadelineProto,$conf) {
    yield $MadelineProto->start();
    $servers = [
        Socket\listen("localhost:".$conf->toPort($conf->whoami()))
    ];
    $logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter);
    $logger = new Logger('server');
    $logger->pushHandler($logHandler);

    $router = new Router;
    $router->addRoute('GET', '/download', 
        new \ShiSHTransferServer\API\Download(
            $MadelineProto
        )
    );
    $router->addRoute('POST', '/upload', 
        new \ShiSHTransferServer\API\Upload(
            $MadelineProto, 
            new \ShiSHTransferServer\Utils\Backup(
                $MadelineProto,
                $conf
            )
        )
    );
    $router->addRoute('GET', '/searchDMS',
        new \ShiSHTransferServer\API\SearchDMS(
            $MadelineProto
        )
    );
    $router->addRoute('GET', '/delete',
    new \ShiSHTransferServer\API\Delete(
        $MadelineProto
    )
);
    $server = new Server($servers, $router, $logger,
        (new Amp\Http\Server\Options)
        ->withoutCompression()
        ->withoutHttp2Upgrade()
        ->withHttp1Timeout(900)
        ->withDebugMode()
        ->withAllowedMethods(['GET', 'POST', 'HEAD'])
        ->withConnectionsPerIpLimit(10000)
        ->withChunkSize(1048576)
    );
    yield $server->start();
});
