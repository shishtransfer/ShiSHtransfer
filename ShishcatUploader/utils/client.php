<?php
namespace ShishTransfer\Utils;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use function \Amp\call;
use \Amp\Success;

class Client
{
    protected $client;
    protected $links;
    protected $bslittt = 999999999999999;
    function __construct(&$client,&$links)
    {
        $this->client = $client;
        $this->links = $links;
    }
    function ReturnUpload(&$previousReadDiscard,$size,$body)
    {
        return call(function(&$previousReadDiscard,$size,$body){
            $username = array_rand($this->links);
            $url = $this->links[$username];
            $url = "$url/upload";
            $request  = new \Amp\Http\Client\Request($url);
            $request->setMethod("POST");
            $request->setProtocolVersions(["1.1"]);
            $request->setBodySizeLimit($this->bslittt);
            $request->setTransferTimeout($this->bslittt);
            $request->setInactivityTimeout(0);
            $request->setBody(new \ShishTransfer\Utils\UploadRequestBody($previousReadDiscard,$body,$size));
            $sresp = yield ((yield $this->client->request($request))->getBody())->buffer();
            unset($url,$request);
            return [
                $sresp,
                $username
            ];
        },$previousReadDiscard,$size,$body);
    }
    function GetTGFile($acc,$id,$range = "",$mirrorpeer,$mirrorid,$nrec=true)
    {
        return call(function($acc,$id,$range,$mirrorpeer,$mirrorid,$nrec){
            $request  = new \Amp\Http\Client\Request($this->links[$acc]."/download?id=$id");
            $request->setMethod("GET");
            $request->setProtocolVersions(["1.1"]);
            $request->setBodySizeLimit($this->bslittt);
            $request->setTransferTimeout($this->bslittt);
            $request->setInactivityTimeout(0);
            if($range !== ""){
                $request->setHeader("Range",$range);
            }
            $response = yield $this->client->request($request);
            if($nrec&&($response->getStatus() !== 206&&$response->getStatus() !== 200)){
                return $this->GetTGFile($mirrorpeer,$mirrorid,$range,"",0,false);
            } elseif($response->getStatus() !== 206&&$response->getStatus() !== 200){
                throw new \Exception("Corrupt file: ".$response->getStatus());
            }
            return $response->getBody();
        },$acc,$id,$range,$mirrorpeer,$mirrorid,$nrec);
    }
    function DeleteFile($acc,$ids)
    {
        return call(function($acc,$ids){
            $request  = new \Amp\Http\Client\Request($this->links[$acc]."/delete?".json_encode($ids));
            $request->setMethod("GET");
            $request->setProtocolVersions(["1.1"]);
            $request->setBodySizeLimit($this->bslittt);
            $request->setTransferTimeout($this->bslittt);
            $request->setInactivityTimeout(0);
            $response = yield $this->client->request($request);
            $response = $response->getBody();
            yield $response->buffer();
            return;
        },$acc,$ids);
    }
};