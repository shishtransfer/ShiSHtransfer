<?php
namespace ShishTransfer\API;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Router;
use function \Amp\call;
use \Amp\Http\Server\HttpServer;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\StaticContent\DocumentRoot;
use \Amp\Log\ConsoleFormatter;
use \Amp\Log\StreamHandler;
use \Amp\Loop;
use \Amp\Promise;
use \Amp\Socket\Server;
use \Amp\Success;
use \Amp\Websocket\Client;
use \Amp\Websocket\Message;
use \Amp\Websocket\Server\ClientHandler;
use \Amp\Websocket\Server\Gateway;
use \Monolog\Logger;
use function \Amp\ByteStream\getStdout;

class RefsWS implements ClientHandler {
    private $users; // ["telegramid"=>[$client1,$client2]]
    public $verifyButtonData;
    protected $client;
    protected $db;
    protected $redis;
    protected $gateway;
    public function __construct(&$client,&$db,&$redis){
        $this->verifyButtonData = new \verifyButtonData;
        $this->db = $db;
        $this->client = $client;
        $this->redis = $redis;
        $this->users = [];
    }
    public function handleHandshake(Gateway $gateway, Request $request, Response $response): Promise
    {
        return new Success($response);
    }

    public function handleClient(Gateway $gateway, Client $client, Request $request, Response $response): Promise
    {
        return call(function () use ($gateway, $client) {
            $this->gateway = $gateway;
            $message = yield $client->receive();
            if($message ==  null){ return; }
            $message = yield $message->buffer();
            $res = json_decode($message,true);
            $id = $res["id"]??"";
            if(!(yield $this->verifyButtonData->verify($res))){
                $gateway->multicast(json_encode([
                    "op"=>0
                ]),[$client->getId()]);
                return;
            }
            unset($res);
            $gateway->multicast(json_encode([
                "op"=>1
            ]),[$client->getId()]);
            if(isset($this->users[$id])){
                $this->users[$id][] = $client->getId();
            } else {
                $this->users[$id] = [$client->getId()];
            }
            $statement = yield $this->db->prepare("SELECT * FROM refs WHERE `tgid` = :id");
            $result = yield $statement->execute(['id' => $id]);
            while (yield $result->advance()) {
                $refs = $result->getCurrent();  
                $realmsg = $this->db2api($refs);
                $gateway->multicast(json_encode([
                    "op"=>2,
                    "file"=>$realmsg
                ]),[$client->getId()]);
            }
            while ($message = yield $client->receive()) {
                if($message ==  null){
                    echo "disco";
                    unset($this->users[$id][array_search($client->getId(),$this->users[$id])]);
                    return;
                }
            }
        });
    }
    public function send($id,$info){
        return call(function () use ($id,$info) 
        {
            if(empty($this->users[$id])){
                echo "no users\n";
                return;
            }
            echo "send\n";
            return $this->gateway->multicast(json_encode($info),$this->users[$id]);
        });
    }
    function db2api($dbdata){
        $realmsg = [];
        $realmsg["id"] = $dbdata["id1"];
        $realmsg["fname"] = $dbdata["fname"];
        $realmsg["size"] = $dbdata["size"];
        $realmsg["hash"] = $dbdata["hash"];
        $realmsg["flink"] = $dbdata["id"];
        return $realmsg;
    }
    function api2db($tgid,$apidata){
        $dbdata = [];
        $dbdata["tgid"] = $tgid;
        $dbdata["id1"] = $apidata["id"];
        $dbdata["hash"] = $apidata["hash"];
        $dbdata["hash_"] = $apidata["hash"];
        $dbdata["id"] = $apidata["flink"];
        $dbdata["fname"] = $apidata["fname"];
        $dbdata["size"] = $apidata["size"];
        return $dbdata;
    }
};