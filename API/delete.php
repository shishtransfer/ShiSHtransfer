<?php

namespace ShiSHTransferServer\API;

use \Amp;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\Response;
use \Amp\Promise;
use \danog;
use function \Amp\call;

class Delete implements \Amp\Http\Server\RequestHandler
{
    private \danog\MadelineProto\API $MadelineProto;

    public function __construct(&$MadelineProto)
    {
        $this->MadelineProto = $MadelineProto;
    }
    public function handleRequest(Request $request): \Amp\Promise
    {
        $callable = function (Request $request) {
            $ffs = json_decode(urldecode($request->getUri()->getQuery()));
            foreach($ffs as $v){
                $msgs = (yield $this->MadelineProto->messages->deleteMessages(['revoke'=>true,'id' => [$v]]));
            }
            return new Response(200,[],null);
        };
        return call($callable, $request);
    }
}
