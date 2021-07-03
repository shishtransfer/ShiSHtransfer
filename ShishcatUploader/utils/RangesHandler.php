<?php

namespace ShishTransfer\Utils;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use function abs;
use function \Amp\call;
use \Amp\Success;
use function count;
use function explode;
use function intval;
use function min;

class RangesHandler
{
    public static function getRanges($range, $size, $partsize)
    {
        return call(function ($range, $size, $partsize) {
            $realrange = yield self::getRange($range, $size);
            if (!$realrange) return false;
            if ($realrange[1] <= $partsize) {
                return ["start" => 0, "sendstart" => $realrange[0], "end" => 0, "sendend" => $realrange[1], "header" => "bytes $realrange[0]-$realrange[1]/$size", "contentlength" => ($realrange[1] - $realrange[0]) + 1];
            } else {
                $numstartsend = $realrange[0];
                $numstart = 0;
                while ($numstartsend >= $partsize) {
                    $numstartsend -= $partsize;
                    $numstart++;
                }
                $numendsend = $realrange[1];
                $numend = 0;
                while ($numendsend >= $partsize) {
                    $numendsend -= $partsize;
                    $numend++;
                }
                return (["start" => $numstart, "sendstart" => $numstartsend, "end" => $numend, "sendend" => $numendsend, "header" => "bytes $realrange[0]-$realrange[1]/$size", "contentlength" => ($realrange[1] - $realrange[0]) + 1]);
            }
        }, $range, $size, $partsize);
    }

    public static function getRange($range, $size)
    {
        if (isset($range)) {
            $range = explode('=', $range, 2);
            if (count($range) == 1) {
                $range[1] = '';
            }
            [$size_unit, $range_orig] = $range;
            if ($size_unit == 'bytes') {
                $list = explode(',', $range_orig, 2);
                if (count($list) == 1) {
                    $list[1] = '';
                }
                [$range, $extra_ranges] = $list;
            } else {
                return false;
            }
        } else {
            $range = '';
        }
        $listseek = explode('-', $range, 2);
        if (count($listseek) == 1) {
            $listseek[1] = '';
        }
        [$seek_start, $seek_end] = $listseek;

        $size = $size ?? 0;
        $seek_end = empty($seek_end) ? ($size - 1) : min(abs(intval($seek_end)), $size - 1);
        if (!empty($seek_start) && $seek_end < abs(intval($seek_start))) {
            return false;
        }
        $seek_start = empty($seek_start) ? 0 : abs(intval($seek_start));

        return new Success([$seek_start, $seek_end]);
    }
}
