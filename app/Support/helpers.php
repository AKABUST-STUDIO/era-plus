<?php

if (! function_exists('normalize_string')) {
    function normalize_string(?string $string): string
    {
        return mb_strtolower(trim((string) $string));
    }
}

if (! function_exists('country_flag_emoji')) {
    function country_flag_emoji(?string $iso2): string
    {
        if (blank($iso2) || strlen($iso2) !== 2 || ! ctype_alpha($iso2)) {
            return '';
        }

        return collect(str_split(strtoupper($iso2)))
            ->map(fn (string $letter): string => mb_chr(0x1F1E6 + ord($letter) - ord('A'), 'UTF-8'))
            ->implode('');
    }
}

if (! function_exists('initials')) {
    function initials(?string $name, string $fallback = '·'): string
    {
        if (blank($name)) {
            return $fallback;
        }

        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
