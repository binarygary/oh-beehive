<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Inspection;
use App\Services\InspectionParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseInspectionNotes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Inspection $inspection) {}

    public function handle(InspectionParserService $parser): void
    {
        $parser->parse($this->inspection);
    }
}
