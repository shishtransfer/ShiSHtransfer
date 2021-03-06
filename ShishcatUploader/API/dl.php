<?php
namespace ShishTransfer\API;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Router;
use \Amp\Success;
use function \Amp\call;

class Download implements RequestHandler
{
    protected $client;
    protected $db;
    protected $logger;
    protected $redis;
    public function __construct(&$client,&$db,&$redis,$logger)
    {
        $this->client = $client;
        $this->db = $db;
        $this->redis = $redis;
        $this->logger = $logger;
    }
    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Promise
    {
        $callable = function (\Amp\Http\Server\Request $request) {
            $statement = yield $this->db->prepare("SELECT * FROM dlinkshead WHERE flink = :id");
            $dla = $request->getAttribute(Router::class);
            $result = yield $statement->execute(['id' => $dla["id"]]);
            $meta = [];
            while (yield $result->advance()) {
                $meta = $result->getCurrent();  
                break;
            }
            if(empty($meta)){
                return new Response(404,["content-type"=>"text/plain"],"404");
            }
            $filename = $meta["filename"];
            if($meta["contenttype"] == "text/html") $meta["contenttype"] = "application/octet-stream";
            if($request->getMethod() !== "GET"){
                return new Response(200,["content-type"=>$meta["contenttype"],"content-disposition"=>"inline; filename=\"$filename\""],null);
            }
            $range = $request->getHeader("Range");
            if(!is_null($range)){
                $rrange = yield \ShishTransfer\Utils\RangesHandler::getRanges($range,(int)$meta["contentlength"],$meta["partsize"]);
                if(!is_array($rrange)) return new Response(416,[],null);
                return new Response(206,["content-length"=>$rrange["contentlength"],"content-range"=>$rrange["header"],"content-type"=>$meta["contenttype"],"content-disposition"=>"inline; filename=\"$filename\""],
                    new \Amp\ByteStream\IteratorStream(new \Amp\Producer(function (callable $emit) use($dla,$meta,$rrange){
                        try{
                        $rcount = 0;
                        $starte = false;
                        $statement = yield $this->db->prepare("SELECT * FROM dllinks WHERE flink = :id ORDER BY uniq ASC");
                        $result = yield $statement->execute(['id' => $dla["id"]]);
                        while (yield $result->advance()) {
                            $row = $result->getCurrent();
                            if($rcount == $rrange["start"]&&$rcount == $rrange["end"]){
                                $startRange = $rrange["sendstart"];
                                $endRange = $rrange["sendend"];
                                $tbody = yield $this->client->GetTGFile($row["username"],(int)$row["msgid"],"bytes=$startRange-$endRange",$row["mirror_username"],$row["mirror_id"]);
                                while (($chunk = yield $tbody->read()) !== null) {
                                    yield $emit($chunk);
                                }
                                return;
                            } elseif($rcount == $rrange["start"]){
                                $startRange = $rrange["sendstart"];
                                $tbody = yield $this->client->GetTGFile($row["username"],(int)$row["msgid"],"bytes=$startRange-",$row["mirror_username"],$row["mirror_id"]);
                                while (($chunk = yield $tbody->read()) !== null) {
                                    yield $emit($chunk);
                                }
                                $starte = true;
                                continue;
                            } elseif($starte) {
                                $tbody = yield $this->client->GetTGFile($row["username"],(int)$row["msgid"],"",$row["mirror_username"],$row["mirror_id"]);
                                while (($chunk = yield $tbody->read()) !== null) {
                                    yield $emit($chunk);
                                }
                                continue;
                            } elseif($rcount == $rrange["end"]){
                                $startRange = $rrange["start"];
                                $tbody = yield $this->client->GetTGFile($row["username"],(int)$row["msgid"],"bytes=0-$startRange",$row["mirror_username"],$row["mirror_id"]);
                                while (($chunk = yield $tbody->read()) !== null) {
                                    yield $emit($chunk);
                                }
                                return;
                            }
                            $rcount++;
                        }
                        yield $this->redis->increment("st_bandwidth",$rrange["contentlength"]);
                        return;
                        } catch (\Throwable $tes){
                            return;
                        }
                    }))
                );
            }
            return new Response(200,["content-type"=>$meta["contenttype"],"content-length"=>$meta["contentlength"],"content-disposition"=>"inline; filename=\"$filename\""],
                new \Amp\ByteStream\IteratorStream(new \Amp\Producer(function (callable $emit) use($dla,$meta){
                    $statement = yield $this->db->prepare("SELECT * FROM dllinks WHERE flink = :id ORDER BY uniq ASC");
                    $result = yield $statement->execute(['id' => $dla["id"]]);
                    while (yield $result->advance()) {
                        $row = $result->getCurrent();
                        $tbody = yield $this->client->GetTGFile($row["username"],(int)$row["msgid"],"",$row["mirror_username"],$row["mirror_id"]);
                        while (($chunk = yield $tbody->read()) !== null) {
                            yield $emit($chunk);
                        }
                    }
                    yield $this->redis->increment("st_bandwidth",$meta["contentlength"]);
                    return;
                }))
            );
        };
        return call($callable, $request);
    }
    public function reset()
    {
        return call(function () {});
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
    public function MinuteReset()
    {
        return call(function () {});
    }
}
