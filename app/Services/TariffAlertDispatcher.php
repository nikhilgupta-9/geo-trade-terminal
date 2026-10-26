<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TariffAlert;
use App\Models\TariffRevision;
use App\Models\UserWatchlist;
use Illuminate\Support\Facades\Log;

class TariffAlertDispatcher
{
    public function __construct(
        protected LandedCostEngine $engine
    ) {}

    /**
     * Dispatch alerts for a newly recorded tariff revision.
     *
     * @return list<TariffAlert>
     */
    public function dispatchForRevision(TariffRevision $revision): array
    {
        $revision->loadMissing('hsCode');
        $hsCode = $revision->hsCode;

        if (!$hsCode) {
            return [];
        }

        $watchlists = UserWatchlist::where('hs_code_id', $hsCode->id)
            ->where('is_active', true)
            ->with('user')
            ->get();

        $generatedAlerts = [];

        foreach ($watchlists as $watchlist) {
            // Check increase-only filter
            if ($watchlist->notify_on_increase_only && $revision->action !== 'INCREASE') {
                continue;
            }

            // Estimate financial impact based on user's baseline FOB & volume
            $baselineFob = (float) ($watchlist->baseline_fob_inr ?? 100000.00);
            $volume = (int) ($watchlist->monthly_volume_units ?? 1);

            $impact = $this->engine->evaluateWatchlistImpact(
                hsCode: $hsCode,
                baselineFobInr: $baselineFob,
                monthlyVolume: $volume,
                oldBcd: (float) $revision->old_rate,
                newBcd: (float) $revision->new_rate
            );

            $alert = TariffAlert::create([
                'user_id' => $watchlist->user_id,
                'hs_code_id' => $hsCode->id,
                'revision_id' => $revision->id,
                'old_rate' => $revision->old_rate,
                'new_rate' => $revision->new_rate,
                'delta' => $revision->delta ?? ($revision->new_rate - $revision->old_rate),
                'estimated_monthly_impact_inr' => $impact['monthly_financial_impact_inr'],
                'status' => 'UNREAD',
                'channel' => 'IN_APP',
            ]);

            $generatedAlerts[] = $alert;

            Log::channel('single')->info("Tariff Alert Dispatched for User {$watchlist->user_id} on HS {$hsCode->code}: delta {$alert->delta}%, Monthly Impact ₹{$alert->estimated_monthly_impact_inr}");
        }

        return $generatedAlerts;
    }
}
