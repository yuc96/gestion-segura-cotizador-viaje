<?php

declare(strict_types=1);

namespace App\Application\Port;

interface ActivityLog
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $event, array $context = []): void;

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $event, array $context = []): void;
}
