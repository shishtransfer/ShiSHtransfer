<?php
namespace ShiSHTransferServer\Utils;

use \Amp\Promise;
use \Amp\Success;
use function \Amp\call;

class Backup
{
    private \danog\MadelineProto\API $MadelineProto;
    private $conf;
    private $httpClient;
    

    public function __construct(&$MadelineProto,&$conf)
    {
        $this->conf = $conf;
        $this->MadelineProto = $MadelineProto;
        $this->httpClient = \Amp\Http\Client\HttpClientBuilder::buildDefault();
    }
    public function mkmirror($id, $hash)
    {
        $callable = function ($id, $hash) {
            //make remote copies
                $backup_ = yield $this->MadelineProto->messages->forwardMessages([
                    'from_peer' => '@me',
                    'to_peer' => $this->conf->getBackup(),
                    'id' => [
                        $id
                    ]
                ]);
                $backup = $backup_["updates"][1]["message"]["id"];
            //make easy accessible copies
                $forwarded = yield $this->MadelineProto->messages->forwardMessages([
                    'from_peer' => '@me',
                    'to_peer' => '@'.$peer = $this->conf->getRandAccc(),
                    'id' => [
                        $id
                    ]
                ]);
                $id = $forwarded["updates"][1]["message"]["id"];
            //get access to the file from both peers
                $remoteID = yield $this->obtainPeerID($hash,$peer);
                $return = [
                    "myid"=>$id,
                    "remotepeer"=>null,
                    "peer"=>$peer,
                    "backup_id"=>$backup
                ];
                if(!is_numeric($remoteID)){
                    echo "ERROR: Failed to access peerid, no mirror for file $hash\n";
                } else {
                    $return["remotepeer"] = $remoteID;
                }
            return $return;
        };
        return call($callable, $id, $hash);
    }
    private function obtainPeerID($hash, $peer){
        return call(function($hash, $peer){
            try{
                $request = new \Amp\Http\Client\Request("http://localhost:".$this->conf->toPort($peer)."/searchDMS?".http_build_query([
                    "q"=>$hash,
                    "peer"=>"@".$this->conf->whoami()
                ]));
                $request->setMethod("GET");
                $request->setProtocolVersions(["1.1"]);
                $request->setBodySizeLimit(99999999999999);
                $request->setTransferTimeout(99999999999999);
                $request->setInactivityTimeout(0);
                return json_decode(yield ((yield $this->httpClient->request($request))->getBody())->buffer());
            } catch (\Throwable $exc){
                echo $exc;
                return null;
            }
        },$hash, $peer);
    }
}
