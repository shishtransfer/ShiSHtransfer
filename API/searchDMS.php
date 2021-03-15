<?php

namespace ShiSHTransferServer\API;

use \Amp;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\Response;
use \Amp\Promise;
use \danog;
use function \Amp\call;

class SearchDMS implements \Amp\Http\Server\RequestHandler
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
            $peer = $pquery["peer"] ?? null;
            $q = $pquery["q"] ?? null;
            if((!$q)||(!$peer)) return new Response(400,[],"null");
            $search = yield $this->MadelineProto->messages->search(['peer' => $peer, 'q' => $q, 'min_date' => 0, 'max_date' => 0, 'offset_id' => 0, 'add_offset' => 0, 'limit' => 100, 'max_id' => 0, 'min_id' => 0]);
            if (\count($search['messages']) == 0) {
                return new Response(404,[],null);
            }
            foreach ($search['messages'] as $message) {
                $e = $message['message'] ?? '';
                if ($e == '') {
                    continue;
                }
                if($e == $q){
                    return new Response(200,[],json_encode($message["id"]));
                }
                continue;
            }
            return $dl;
        };
        return call($callable, $request);
    }
}
