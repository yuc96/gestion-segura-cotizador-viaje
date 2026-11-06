<?php

declare(strict_types=1);

namespace App\Actions\Quotes;

use App\Domain\Quotes\QuoteStatus;
use App\Exceptions\QuoteAlreadyContracted;
use App\Models\Quote;

final class ContractQuote
{
    public function handle(Quote $quote): Quote
    {
        if ($quote->status === QuoteStatus::Contracted) {
            throw new QuoteAlreadyContracted('Esta cotización ya fue contratada.');
        }

        $quote->status = QuoteStatus::Contracted;
        $quote->contracted_at = now();
        $quote->save();

        return $quote->load('insured');
    }
}
