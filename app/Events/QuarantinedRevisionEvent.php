<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\QuarantinedTariffRevision;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuarantinedRevisionEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public QuarantinedTariffRevision $quarantined
    ) {
        $this->quarantined->loadMissing(['circular', 'matchedHsCode']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.review-queue'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'revision.quarantined';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->quarantined->id,
            'circular_id' => $this->quarantined->circular_id,
            'source' => $this->quarantined->circular?->source,
            'notification_number' => $this->quarantined->circular?->notification_number,
            'raw_hs_code' => $this->quarantined->raw_hs_code,
            'matched_code' => $this->quarantined->matchedHsCode?->code,
            'extracted_old_rate' => $this->quarantined->extracted_old_rate,
            'extracted_new_rate' => $this->quarantined->extracted_new_rate,
            'confidence_score' => $this->quarantined->confidence_score,
            'quarantine_reason' => $this->quarantined->quarantine_reason,
            'status' => $this->quarantined->status,
            'created_at' => $this->quarantined->created_at?->toIso8601String(),
        ];
    }
}
