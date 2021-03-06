<?php
namespace ShishTransfer\API;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Router;
use function \Amp\call;

class Delete implements RequestHandler
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
            $statement = yield $this->db->prepare("SELECT * FROM dlinkshead WHERE `hash` = :id");
            $dla = $request->getAttribute(Router::class);
            $result = yield $statement->execute(['id' => $dla["hash"]]);
            while (yield $result->advance()) {
                $meta = $result->getCurrent();  
                break;
            }
            if(!isset($meta)){
                return new Response(404,["content-type"=>"text/plain"],"404");
            }
            $statement = yield $this->db->prepare("SELECT * FROM dllinks WHERE flink = :id ORDER BY uniq ASC");
            $result = yield $statement->execute(['id' => $meta["flink"]]);
            while (yield $result->advance()) {
                $row = $result->getCurrent();
                yield $this->client->DeleteFile($row["username"],[$row["msgid"],$row["myid"],$row["backup_id"]]);
            }
            $statement = yield $this->db->prepare("DELETE FROM `dllinks` WHERE `flink` = :id");
            $result = yield $statement->execute(['id' => $meta["flink"]]);
            $statement = yield $this->db->prepare("DELETE FROM `dlinkshead` WHERE `flink` = :id");
            $result = yield $statement->execute(['id' => $meta["flink"]]);
            yield $this->redis->decrement("st_used",$meta["contentlength"]);
            return new Response(200,["access-control-allow-origin"=>"*","content-type"=>"application/json"],"{\"ok\":true}");
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