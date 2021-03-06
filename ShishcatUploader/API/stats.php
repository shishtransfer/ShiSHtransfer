<?php
namespace ShishTransfer\API;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Router;
use function \Amp\call;

class Stats implements RequestHandler
{
    protected $client;
    protected $db;
    protected $redis;
    public function __construct(&$client,&$db,&$redis)
    {
        $this->db = $db;
        $this->client = $client;
        $this->redis = $redis;
    }
    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Promise
    {
        $callable = function (\Amp\Http\Server\Request $request) {
            $storage = yield $this->redis->get("st_used");
            $bw = yield $this->redis->get("st_bandwidth");
            return new Response(200,["access-control-allow-origin"=>"*","content-type"=>"application/json"],json_encode([
                "bandwidth"=>$this->formatBytes($bw),
                "bandwidth_raw"=>$bw,
                "storage"=>$this->formatBytes($storage),
                "storage_raw"=>$storage
            ]));
        };
        return call($callable, $request);
    }
    function formatBytes($size, $precision = 3)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');   
        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }
}