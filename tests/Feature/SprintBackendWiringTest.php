<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\LiveTariffTickerEvent;
use App\Events\QuarantinedRevisionEvent;
use App\Events\TariffAlertDispatchedEvent;
use App\Models\HsCode;
use App\Models\QuarantinedTariffRevision;
use App\Models\RegulatoryCircular;
use App\Models\TariffAlert;
use App\Models\TariffRevision;
use App\Models\User;
use App\Models\UserWatchlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SprintBackendWiringTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;
    protected User $admin;
    protected HsCode $solar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trader = User::factory()->create([
            'role' => 'TRADER',
            'company' => 'Apex Global Commodities',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'ADMIN',
            'company' => 'Geo Trade Terminal HQ',
        ]);

        $this->solar = HsCode::create([
            'code' => '85414300',
            'chapter' => '85',
            'description' => 'Solar cells, assembled in modules or made up into panels',
            'unit' => 'NOS',
            'base_bcd_rate' => 25.0,
            'sws_rate' => 10.0,
            'igst_rate' => 12.0,
            'aidc_rate' => 0.0,
            'is_active' => true,
        ]);
    }

    public function test_hs_code_autocomplete_search(): void
    {
        $response = $this->getJson('/api/v1/hs-codes/search?q=8541');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('count', 1);
        $response->assertJsonPath('data.0.code', '85414300');
    }

    public function test_public_throttled_landed_cost_simulation(): void
    {
        $response = $this->postJson('/api/v1/simulate/landed-cost', [
            'unit_fob' => 180,
            'quantity' => 1000,
            'currency' => 'USD',
            'hs_code' => '85414300',
            'target_selling_price' => 25000,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('hs_code.code', '85414300');
        $response->assertJsonPath('data.delta.action', 'UNCHANGED');
        $this->assertNotNull($response->json('data.baseline.total_landed_cost_inr'));
    }

    public function test_gazette_notifications_filterable_by_authority_and_date(): void
    {
        RegulatoryCircular::create([
            'source' => 'CBIC_CUSTOMS',
            'notification_number' => '14/2026-Customs',
            'title' => 'CBIC Customs Gazette Circular',
            'issue_date' => '2026-10-24',
            'effective_date' => '2026-10-27',
            'parse_status' => 'PARSED',
        ]);

        RegulatoryCircular::create([
            'source' => 'DGFT',
            'notification_number' => '38/2025-2026',
            'title' => 'DGFT Trade Policy Circular',
            'issue_date' => '2026-10-22',
            'effective_date' => '2026-10-25',
            'parse_status' => 'PARSED',
        ]);

        // Filter CBIC
        $resCbic = $this->getJson('/api/v1/gazettes?authority=CBIC_CUSTOMS');
        $resCbic->assertStatus(200);
        $resCbic->assertJsonPath('count', 1);
        $resCbic->assertJsonPath('data.0.notification_number', '14/2026-Customs');

        // Filter DGFT
        $resDgft = $this->getJson('/api/v1/gazettes?authority=DGFT');
        $resDgft->assertStatus(200);
        $resDgft->assertJsonPath('count', 1);
        $resDgft->assertJsonPath('data.0.notification_number', '38/2025-2026');
    }

    public function test_watchlist_delete_by_hs_code_in_body(): void
    {
        $this->actingAs($this->trader);
        session(['active_user_id' => $this->trader->id]);

        UserWatchlist::create([
            'user_id' => $this->trader->id,
            'hs_code_id' => $this->solar->id,
            'baseline_fob_inr' => 2500000,
            'monthly_volume_units' => 8,
        ]);

        $delRes = $this->deleteJson('/api/v1/watchlist', [
            'hs_code' => '85414300',
        ]);

        $delRes->assertStatus(200);
        $delRes->assertJsonPath('success', true);
        $this->assertDatabaseMissing('user_watchlists', [
            'user_id' => $this->trader->id,
            'hs_code_id' => $this->solar->id,
        ]);
    }

    public function test_quarantined_tariff_revisions_model_and_websocket_events(): void
    {
        Event::fake([
            LiveTariffTickerEvent::class,
            TariffAlertDispatchedEvent::class,
            QuarantinedRevisionEvent::class,
        ]);

        $circular = RegulatoryCircular::create([
            'source' => 'CBIC_CUSTOMS',
            'notification_number' => '101/2026-Customs',
            'title' => 'Sample Circular',
            'issue_date' => now()->toDateString(),
            'effective_date' => now()->toDateString(),
            'parse_status' => 'PARSED',
        ]);

        $revision = TariffRevision::create([
            'circular_id' => $circular->id,
            'hs_code_id' => $this->solar->id,
            'duty_type' => 'BCD',
            'old_rate' => 25.0,
            'new_rate' => 40.0,
            'action' => 'INCREASE',
            'effective_from' => now()->toDateString(),
        ]);

        $alert = TariffAlert::create([
            'user_id' => $this->trader->id,
            'hs_code_id' => $this->solar->id,
            'revision_id' => $revision->id,
            'old_rate' => 25.0,
            'new_rate' => 40.0,
            'delta' => 15.0,
            'estimated_monthly_impact_inr' => 4000000,
            'status' => 'UNREAD',
        ]);

        $quarantine = QuarantinedTariffRevision::create([
            'circular_id' => $circular->id,
            'raw_hs_code' => '99999999',
            'quarantine_reason' => 'RATE_ANOMALY',
            'extracted_old_rate' => 10.0,
            'extracted_new_rate' => 190.0,
            'confidence_score' => 0.50,
            'status' => 'PENDING_REVIEW',
        ]);

        event(new LiveTariffTickerEvent($revision));
        event(new TariffAlertDispatchedEvent($alert));
        event(new QuarantinedRevisionEvent($quarantine));

        Event::assertDispatched(LiveTariffTickerEvent::class);
        Event::assertDispatched(TariffAlertDispatchedEvent::class);
        Event::assertDispatched(QuarantinedRevisionEvent::class);

        $this->assertDatabaseHas('quarantined_tariff_revisions', [
            'raw_hs_code' => '99999999',
            'status' => 'PENDING_REVIEW',
        ]);
    }
}
