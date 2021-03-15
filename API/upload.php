<?php

namespace ShiSHTransferServer\API;

use \Amp\Http\Server\Request;
use \Amp\Http\Server\Response;
use \Amp\Promise;
use function \Amp\call;

class Upload implements \Amp\Http\Server\RequestHandler
{
    private \danog\MadelineProto\API $MadelineProto;
    private $mirrorHelper;

    public function __construct(&$MadelineProto,$mh)
    {
        $this->MadelineProto = $MadelineProto;
        $this->mirrorHelpher = $mh;
    }
    public function handleRequest(Request $request): \Amp\Promise
    {
        $callable = function (Request $request) {
            $request->getBody()->increaseSizeLimit(2097152000);
            $tsize = 0;
            $size = $request->getHeader("content-length");
            if(!$size) return new Response(400,[],null);
            $ct = "application/zip";
            $hash = bin2hex(random_bytes(16));
            $f = yield from $this->MadelineProto->API->uploadFromStream($request->getBody(), $size, $ct, "$hash.zip");
            $sentMessage = yield $this->MadelineProto->messages->sendMedia([
                'peer' => "@me",
                "message" => $hash,
                'media' => [
                    '_' => 'inputMediaUploadedDocument',
                    'file' => $f,
                    'attributes' => [
                        ['_' => 'documentAttributeFilename', 'file_name' => "$hash.zip"]
                    ]
                ]
            ]);
            $mami = yield $this->mirrorHelpher->mkmirror($sentMessage["updates"][1]["message"]["id"], $hash);
            return new Response(200,["content-type"=>"application/json"],json_encode([
                "mirrors"=>$mami,
                "hash"=>$hash,
                "id"=>$sentMessage["updates"][1]["message"]["id"]
            ]));
        };
        return call($callable, $request);
    }
}
