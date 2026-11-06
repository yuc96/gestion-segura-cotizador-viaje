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

        return self::relevance($nameEs, $nameEn, $code, $query) !== null;
    }

    /**
     * 0 cuando el nombre o el código empiezan por la búsqueda.
     * 1 cuando la búsqueda aparece más adelante.
     * null cuando no hay coincidencia.
     */
    public static function relevance(string $nameEs, string $nameEn, string $code, string $query): ?int
    {
        $needle = self::fold($query);

        if ($needle === '') {
            return 0;
        }

        $fields = array_values(array_filter(
            [self::fold($nameEs), self::fold($nameEn), strtolower($code)],
            static fn (string $field): bool => $field !== '',
        ));

        foreach ($fields as $field) {
            if (str_starts_with($field, $needle)) {
                return 0;
            }
        }

        foreach ($fields as $field) {
            if (str_contains($field, $needle)) {
                return 1;
            }
        }

        return null;
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
