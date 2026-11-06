<?php

declare(strict_types=1);

namespace App\Domain;

use Locale;

final class SpanishCountryName
{
    /** @var array<string, string>|null */
    private static ?array $bundled = null;

    public static function resolve(string $code, string $english): string
    {
        $code = strtoupper($code);
        $fromBundle = self::bundled()[$code] ?? null;

        if (is_string($fromBundle) && $fromBundle !== '') {
            return $fromBundle;
        }

        $icu = self::fromIcu($code);

        if ($icu !== null) {
            return $icu;
        }

        return $english !== '' ? $english : $code;
    }

    public static function fromIcu(string $code): ?string
    {
        if (! class_exists(Locale::class)) {
            return null;
        }

        $display = Locale::getDisplayRegion('und_'.strtoupper($code), 'es');

        if (! is_string($display) || $display === '' || str_starts_with($display, 'und')) {
            return null;
        }

        return $display;
    }

    /**
     * @return array<string, string>
     */
    private static function bundled(): array
    {
        if (self::$bundled !== null) {
            return self::$bundled;
        }

        $path = dirname(__DIR__, 2).'/database/data/countries.json';
        $decoded = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $map = [];

        foreach (is_array($decoded['countries'] ?? null) ? $decoded['countries'] : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = strtoupper((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($code !== '' && $name !== '') {
                $map[$code] = $name;
            }
        }

        return self::$bundled = $map;
    }
}
