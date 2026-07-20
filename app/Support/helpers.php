<?php

if (! function_exists('normalize_string')) {
    function normalize_string(?string $string): string
    {
        return mb_strtolower(trim((string) $string));
    }
}
