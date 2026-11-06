<?php

declare(strict_types=1);

namespace App\Domain;

use Normalizer;

final class CountrySearch
{
    public static function matches(string $nameEs, string $nameEn, string $code, string $query): bool
    {
        $needle = self::fold($query);

        if ($needle === '') {
            return true;
        }

        return str_contains(self::fold($nameEs), $needle)
            || str_contains(self::fold($nameEn), $needle)
            || str_contains(strtolower($code), $needle);
    }

    public static function fold(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        if (class_exists(Normalizer::class)) {
            $decomposed = Normalizer::normalize($value, Normalizer::FORM_D);
            $value = preg_replace('/\p{M}/u', '', is_string($decomposed) ? $decomposed : $value) ?? $value;
        }

        return $value;
    }
}
