<?php
namespace ShishTransfer\Utils;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use danog\MadelineProto\Exception;
use function \Amp\call;
use \Amp\Success;

class UploadRequestBody implements \Amp\Http\Client\RequestBody {
    private $lastreq;
    private $rbs;
    private $singlefilesize;
    private $previousReadDiscard;
    private $currentByteSize = 0;

    function __construct(&$previousReadDiscard,$reqbodystream,$singlefilesize){
        $this->rbs = $reqbodystream;
        $this->singlefilesize = $singlefilesize;
        $this->previousReadDiscard =& $previousReadDiscard;
    }
    function getHeaders() : \Amp\Promise{
        return new Success(['content-type' => 'application/octet-stream']);
    }
    function createBodyStream() : \Amp\ByteStream\IteratorStream
    {
        if(!$this->lastreq){
            $this->lastreq = true;
            return new \Amp\ByteStream\IteratorStream(new \Amp\Producer(function (callable $emit) {
                $this->currentByteSize = 0;
                if($this->previousReadDiscard->previousReadDiscard !== ""){
                    $this->currentByteSize = $cbstr = strlen($this->previousReadDiscard->previousReadDiscard);
                    if($this->currentByteSize > $this->singlefilesize) {
                        $discardedBytesNCount = $this->currentByteSize-$this->singlefilesize;
                        $stra = substr($this->previousReadDiscard->previousReadDiscard,0,-$discardedBytesNCount);
                        $this->previousReadDiscard->previousReadDiscard = substr($this->previousReadDiscard->previousReadDiscard,-$discardedBytesNCount);
                        yield $emit($stra);
                        return;
                    }
                    yield $emit($this->previousReadDiscard->previousReadDiscard);
                    $this->previousReadDiscard->previousReadDiscard = "";
                } else {
                }
                while (($this->singlefilesize > $this->currentByteSize)&&(($chunk = yield $this->rbs->read()) !== null)) {
                    $strlenchunk = strlen($chunk);
                    $this->currentByteSize+=$strlenchunk;
                    if($this->singlefilesize == $this->currentByteSize){
                        yield $emit($chunk);
                        unset($chunk);
                        return;
                    } elseif($this->singlefilesize < $this->currentByteSize){
                        $discardedBytesNCount = $this->currentByteSize-$this->singlefilesize;
                        $this->previousReadDiscard->previousReadDiscard = substr($chunk,-$discardedBytesNCount);
                        yield $emit(substr($chunk,0,-$discardedBytesNCount));
                        unset($chunk);
                        return;
                    } else {
                        yield $emit($chunk);
                        unset($chunk);
                        continue;
                    }
                }
                unset($emit);
            }));
        } else {
            throw new Exception("Error!");
        }
    }
    function getBodyLength() : \Amp\Promise{
        return new Success($this->singlefilesize);
    }
    function __destruct(){
        gc_collect_cycles();
    }
    function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');   
        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }
};