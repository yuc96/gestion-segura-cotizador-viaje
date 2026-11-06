<?php

declare(strict_types=1);

namespace App\Domain;

enum QuoteStatus: string
{
    case Quoted = 'Cotizado';
    case Contracted = 'Contratado';
}
