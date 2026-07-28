<?php

namespace App\Support;

class ImportCell
{
    public static function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = str_replace(["\xC2\xA0", "\u{200B}", "\u{FEFF}"], ' ', (string) $value);
        $string = trim($string);

        return $string === '' ? null : $string;
    }
}
