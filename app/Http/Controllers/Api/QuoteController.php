<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\ContractQuote;
use App\Application\CreateQuote;
use App\Application\CreateQuoteCommand;
use App\Application\DownloadQuotePdf;
use App\Application\GetQuote;
use App\Application\ListQuotes;
use App\Application\QuoteCriteria;
use App\Domain\Quote;
use App\Http\Controllers\Controller;
use App\Http\QuotePresenter;
use App\Http\Requests\IndexQuotesRequest;
use App\Http\Requests\StoreQuoteRequest;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class QuoteController extends Controller
{
    public function index(IndexQuotesRequest $request, ListQuotes $action): JsonResponse
    {
        $validated = $request->validated();
        $page = $action->handle(new QuoteCriteria(
            search: $validated['search'] ?? null,
            status: $validated['status'] ?? null,
            departureFrom: $validated['departure_from'] ?? null,
            departureTo: $validated['departure_to'] ?? null,
            page: $request->integer('page', 1),
            perPage: (int) ($validated['per_page'] ?? 10),
        ));

        return response()->json([
            'data' => array_map(fn (Quote $quote): array => QuotePresenter::make($quote), $page->items),
            'meta' => [
                'current_page' => $page->currentPage,
                'last_page' => $page->lastPage,
                'per_page' => $page->perPage,
                'total' => $page->total,
            ],
        ]);
    }

    public function store(StoreQuoteRequest $request, CreateQuote $action): JsonResponse
    {
        $quote = $action->handle(CreateQuoteCommand::fromArray($request->validated()));

        return response()->json(['data' => QuotePresenter::make($quote)], 201);
    }

    public function show(int $quote, GetQuote $action): JsonResponse
    {
        return response()->json([
            'data' => QuotePresenter::make($action->handle($quote)),
        ]);
    }

    public function contract(int $quote, ContractQuote $action): JsonResponse
    {
        $contracted = $action->handle($quote, new DateTimeImmutable('now'));

        return response()->json(['data' => QuotePresenter::make($contracted)]);
    }

    public function pdf(int $quote, DownloadQuotePdf $action): Response
    {
        $document = $action->handle($quote);

        return response($document->contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$document->filename.'"',
        ]);
    }
}
