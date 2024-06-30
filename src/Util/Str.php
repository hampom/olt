<?php

namespace Olt\Util;

final readonly class Str
{
    public static function multiStrlen(string $str): int
    {
        $byte = strlen($str);
        $count = mb_strlen($str, "UTF8");
        return $byte - ($byte - $count) / 2;
    }
}
