<?php

namespace App\Providers;

use App\Application\Port\ActivityLog;
use App\Application\Port\CountryProvider;
use App\Application\Port\PdfGenerator;
use App\Application\Port\QuoteRepository;
use App\Infrastructure\Countries\GraphqlCountryProvider;
use App\Infrastructure\Logging\JsonActivityLog;
use App\Infrastructure\Pdf\DompdfQuotePdf;
use App\Infrastructure\Persistence\EloquentQuoteRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(QuoteRepository::class, EloquentQuoteRepository::class);
        $this->app->bind(CountryProvider::class, GraphqlCountryProvider::class);
        $this->app->bind(PdfGenerator::class, DompdfQuotePdf::class);
        $this->app->bind(ActivityLog::class, JsonActivityLog::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
