<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Port\ActivityLog;
use Illuminate\Support\Facades\Log;

final class JsonActivityLog implements ActivityLog
{
    public function info(string $event, array $context = []): void
    {
        Log::channel('json')->info($event, ['event' => $event] + $context);
    }

    public function warning(string $event, array $context = []): void
    {
        Log::channel('json')->warning($event, ['event' => $event] + $context);
    }
}
