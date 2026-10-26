<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TariffAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TariffAlertDispatchedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TariffAlert $alert
    ) {
        $this->alert->loadMissing(['hsCode', 'revision.circular']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->alert->user_id}.alerts"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tariff.alert.dispatched';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->alert->id,
            'user_id' => $this->alert->user_id,
            'hs_code' => $this->alert->hsCode?->code,
            'old_rate' => $this->alert->old_rate,
            'new_rate' => $this->alert->new_rate,
            'delta' => $this->alert->delta,
            'estimated_monthly_impact_inr' => $this->alert->estimated_monthly_impact_inr,
            'status' => $this->alert->status,
            'source' => $this->alert->revision?->circular?->source,
            'notification_number' => $this->alert->revision?->circular?->notification_number,
            'created_at' => $this->alert->created_at?->toIso8601String(),
        ];
    }
}
