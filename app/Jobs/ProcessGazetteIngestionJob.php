<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\RegulatoryCircular;
use App\Services\GazetteParserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessGazetteIngestionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public int $circularId
    ) {
        $this->onQueue('gazette-ingestion');
    }

    public function handle(GazetteParserService $parser): void
    {
        $circular = RegulatoryCircular::find($this->circularId);

        if (!$circular) {
            Log::channel('single')->warning("ProcessGazetteIngestionJob: Circular #{$this->circularId} not found.");
            return;
        }

        Log::channel('single')->info("Processing gazette circular {$circular->notification_number} on queue [gazette-ingestion]");

        $revisions = $parser->parseCircular($circular);

        Log::channel('single')->info("Successfully parsed circular #{$circular->id}. Created " . count($revisions) . " tariff revisions.");

        // Queue alert jobs for each revision
        foreach ($revisions as $revision) {
            DispatchTariffAlertsJob::dispatch($revision->id)->onQueue('alerts');
        }
    }
}
