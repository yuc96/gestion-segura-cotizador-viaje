<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;

final class StructuredLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $event, array $context = []): void
    {
        Log::channel('json')->info($event, ['event' => $event] + $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $event, array $context = []): void
    {
        Log::channel('json')->warning($event, ['event' => $event] + $context);
    }
}
