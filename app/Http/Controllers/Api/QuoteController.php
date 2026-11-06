<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Quotes\ContractQuote;
use App\Actions\Quotes\CreateQuote;
use App\Actions\Quotes\CreateQuoteData;
use App\Actions\Quotes\RenderQuotePdf;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexQuotesRequest;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use App\Queries\QuoteIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class QuoteController extends Controller
{
    public function index(IndexQuotesRequest $request, QuoteIndexQuery $query): AnonymousResourceCollection
    {
        return QuoteResource::collection($query->paginate($request->validated()));
    }

    public function store(StoreQuoteRequest $request, CreateQuote $action): JsonResponse
    {
        $quote = $action->handle(CreateQuoteData::fromArray($request->validated()));

        return QuoteResource::make($quote)->response()->setStatusCode(201);
    }

    public function show(Quote $quote): QuoteResource
    {
        $quote->load('insured');

        return QuoteResource::make($quote);
    }

    public function contract(Quote $quote, ContractQuote $action): QuoteResource
    {
        return QuoteResource::make($action->handle($quote));
    }

    public function pdf(Quote $quote, RenderQuotePdf $action): Response
    {
        return $action->download($quote);
    }
}
