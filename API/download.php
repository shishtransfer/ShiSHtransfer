<?php

namespace ShiSHTransferServer\API;

use \Amp;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\Response;
use \Amp\Promise;
use \danog;
use function \Amp\call;

class Download implements \Amp\Http\Server\RequestHandler
{
    private \danog\MadelineProto\API $MadelineProto;

    public function __construct(&$MadelineProto)
    {
        $this->MadelineProto = $MadelineProto;
    }
    public function handleRequest(Request $request): \Amp\Promise
    {
        $callable = function (Request $request) {
            \parse_str($request->getUri()->getQuery(), $pquery);
            if(!$pquery["id"]) return new Response(400,[],null);
            $id = $pquery["id"];
            $msgs = yield $this->MadelineProto->messages->getMessages(['id' => [$id]]);
            $msg = $msgs["messages"][0];
            return yield $this->MadelineProto->downloadToResponse($msg,$request);
        };
        return call($callable, $request);
    }
}
