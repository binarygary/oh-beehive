<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\InspectionParserInterface;
use App\Services\InspectionParserService;
use Illuminate\Support\ServiceProvider;
use OpenAI;
use OpenAI\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function (): Client {
            return OpenAI::client((string) config('services.openai.key'));
        });

        $this->app->bind(InspectionParserInterface::class, InspectionParserService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
