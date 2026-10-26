<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TariffRevision;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveTariffTickerEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TariffRevision $revision
    ) {
        $this->revision->loadMissing(['hsCode', 'circular']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('terminal.live-ticker'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tariff.revision.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->revision->id,
            'hs_code' => $this->revision->hsCode?->code,
            'description' => $this->revision->hsCode?->description,
            'chapter' => $this->revision->hsCode?->chapter,
            'duty_type' => $this->revision->duty_type,
            'old_rate' => $this->revision->old_rate,
            'new_rate' => $this->revision->new_rate,
            'delta' => $this->revision->delta,
            'action' => $this->revision->action,
            'source' => $this->revision->circular?->source,
            'notification_number' => $this->revision->circular?->notification_number,
            'effective_from' => $this->revision->effective_from?->format('Y-m-d'),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
