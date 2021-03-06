<?php
require __DIR__ . "/vendor/autoload.php";
$mdsettings = [
    'connection_settings'=>[
        'all'=>[
            "proxy"=>"\Socket"
        ]
    ]
];
$mdsettings['logger']['logger'] = \danog\MadelineProto\Logger::ECHO_LOGGER;
$mdsettings['flood_timeout']['wait_if_lt'] = 0;
$mdsettings['updates']['handle_updates'] = false;
$mdsettings['serialization']['serialization_interval'] = 60;
$mdsettings['serialization']['cleanup_before_serialization'] = true;
$MadelineProto = new \danog\MadelineProto\API(__DIR__."/sessions/".$argv[1],$mdsettings);
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
});