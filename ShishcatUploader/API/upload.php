<?php
namespace ShishTransfer\API;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use function \Amp\call;

class Upload implements RequestHandler
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
            $request->getBody()->increaseSizeLimit(1024*1024*1024*100);
            $flink = bin2hex(random_bytes(5));
            $ct = $request->getHeader("content-type");
            $totsizec = $request->getHeader("content-length");
            $proof = bin2hex(random_bytes(16));
            $totsize = $request->getHeader("content-length");
            $filename = $request->getHeader("upload-filename");
            $previousReadDiscard = "";
            if(!$filename){
                return new Response(400,["access-control-allow-origin"=>"*","content-type"=>"application/json"],json_encode([
                    "ok"=>false,
                    "error"=>"UNSPECIFIED \"upload-filename\""
                ]));
            }
            $jobid = uniqid();
            echo ("Upload job #$jobid started for a file weighting ".$this->formatBytes($totsize)."\n");
            if(!is_numeric($totsize)) return new Response(400, ["access-control-allow-origin"=>"*","content-type"=>"application/json"], json_encode([
                "ok"=>false,
                "error"=>"NOT NUMERIC CONTENT LENGTH"
            ]));
            $filesparts = [];
            $partsize = rand(6291456,15728640);
            while($totsizec >= $partsize){
                $totsizec -= $partsize;
                $filesparts[] = $partsize;
            }
            if($totsizec != 0){
                $filesparts[] = $totsizec;
            }
            $iteration_ = 0;
            $previousReadDiscard = new \ShishTransfer\Utils\previousReadDiscardHolder;
            foreach($filesparts as $singlefilesize){
                $iteration_++;
                $res = yield $this->client->ReturnUpload($previousReadDiscard,$singlefilesize,$request->getBody());
                $jhs = $res[1];
                $res = json_decode($res[0],true);

                unset($fstream);
                $statement = yield $this->db->prepare("INSERT INTO `dllinks` (`flink`, `username`, `msgid`, `backup_hash`, `mirror_id`, `mirror_username`, `backup_id`, `myid`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $result =    yield $statement->execute([
                    $flink,// /f/ fragment
                    $jhs,//acc username
                    $res["id"],//message id
                    $res["hash"],//hash
                    $res["mirrors"]["remotepeer"]?$res["mirrors"]["remotepeer"]:0,//mirror id
                    $res["mirrors"]["peer"]?$res["mirrors"]["peer"]:"",//mirror username,
                    $res["mirrors"]["backup_id"]?$res["mirrors"]["backup_id"]:"",//local backup id,
                    $res["mirrors"]["myid"]?$res["mirrors"]["myid"]:""
                ]);
                unset($res,$statement,$jhs,$result);
            }
            $statement = yield $this->db->prepare("INSERT INTO `dlinkshead` (`flink`, `contenttype`, `contentlength`, `filename`, `partsize`, `hash`) VALUES (?, ?, ?, ?, ?, ?)");
            $result =    yield $statement->execute([$flink,$ct?$ct:"application/octet-stream","$totsize",substr($filename, -255),"$partsize",$proof]);
            unset($iteration_,$filesparts,$singlefilesize,$previousReadDiscard,$result);
            yield $this->redis->increment("st_bandwidth",$totsize);
            yield $this->redis->increment("st_used",$totsize);
            return new Response(200, ["access-control-allow-origin"=>"*","content-type"=>"application/json"], json_encode([
                "ok"=>true,
                "fLink"=>$flink,
                "hash"=>$proof
            ]));
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
    function formatBytes($size, $precision = 3)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');   
        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }
}