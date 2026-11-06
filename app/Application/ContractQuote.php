<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Port\ActivityLog;
use App\Application\Port\QuoteRepository;
use App\Domain\Exception\QuoteAlreadyContracted;
use App\Domain\Exception\QuoteNotFound;
use App\Domain\Quote;
use DateTimeImmutable;

final class ContractQuote
{
    public function __construct(
        private QuoteRepository $quotes,
        private ActivityLog $log,
    ) {}

    public function handle(int $id, DateTimeImmutable $at): Quote
    {
        $quote = $this->quotes->find($id);

        if ($quote === null) {
            throw new QuoteNotFound('No se encontró la cotización.');
        }

        try {
            $contracted = $quote->contract($at);
        } catch (QuoteAlreadyContracted $exception) {
            $this->log->warning('quote.contract_rejected', [
                'quote_id' => $quote->id,
                'status' => $quote->status->value,
                'reason' => 'already_contracted',
            ]);

            throw $exception;
        }

        $saved = $this->quotes->save($contracted);

        $this->log->info('quote.contracted', [
            'quote_id' => $saved->id,
            'destination_code' => $saved->destination->code,
            'total_cents' => $saved->premium->total->cents,
            'contracted_at' => $saved->contractedAt?->format(DateTimeImmutable::ATOM),
        ]);

        return $saved;
    }
}
