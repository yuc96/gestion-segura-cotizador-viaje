<?php

declare(strict_types=1);

namespace App\Actions\Quotes;

use App\Domain\Quotes\QuoteStatus;
use App\Exceptions\QuoteAlreadyContracted;
use App\Models\Quote;
use App\Support\StructuredLogger;

final class ContractQuote
{
    public function __construct(private StructuredLogger $logger) {}

    public function handle(Quote $quote): Quote
    {
        if ($quote->status === QuoteStatus::Contracted) {
            $this->logger->warning('quote.contract_rejected', [
                'quote_id' => $quote->id,
                'status' => $quote->status->value,
                'reason' => 'already_contracted',
            ]);

            throw new QuoteAlreadyContracted('Esta cotización ya fue contratada.');
        }

        $quote->status = QuoteStatus::Contracted;
        $quote->contracted_at = now();
        $quote->save();
        $quote->load('insured');

        $this->logger->info('quote.contracted', [
            'quote_id' => $quote->id,
            'destination_code' => $quote->destination_code,
            'total_cents' => $quote->total_amount_cents,
            'contracted_at' => $quote->contracted_at?->toIso8601String(),
        ]);

        return $quote;
    }
}
