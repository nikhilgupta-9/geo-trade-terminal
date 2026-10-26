<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\TariffAlertDispatchedEvent;
use App\Models\TariffRevision;
use App\Services\TariffAlertDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DispatchTariffAlertsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public int $revisionId
    ) {
        $this->onQueue('alerts');
    }

    public function handle(TariffAlertDispatcher $dispatcher): void
    {
        $revision = TariffRevision::with('hsCode', 'circular')->find($this->revisionId);

        if (!$revision) {
            Log::channel('single')->warning("DispatchTariffAlertsJob: Revision #{$this->revisionId} not found.");
            return;
        }

        Log::channel('single')->info("Evaluating portfolio watchlists for revision on HS {$revision->hsCode?->code} on queue [alerts]");

        $alerts = $dispatcher->dispatchForRevision($revision);

        Log::channel('single')->info("Generated " . count($alerts) . " alerts for revision #{$revision->id}.");

        // Broadcast real-time WebSocket alert event for each generated alert
        foreach ($alerts as $alert) {
            event(new TariffAlertDispatchedEvent($alert));
        }
    }
}
