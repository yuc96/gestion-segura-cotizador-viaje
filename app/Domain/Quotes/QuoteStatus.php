<?php

declare(strict_types=1);

namespace App\Domain\Quotes;

enum QuoteStatus: string
{
    case Quoted = 'Cotizado';
    case Contracted = 'Contratado';
}
