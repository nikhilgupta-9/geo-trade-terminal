<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HsCode;
use App\Models\RegulatoryCircular;
use App\Models\TariffAlert;
use App\Models\TariffRevision;
use App\Models\User;
use App\Models\UserWatchlist;
use App\Services\GazetteParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GazettePipelineAndAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_gazette_parser_extracts_revisions_and_dispatches_alerts(): void
    {
        // 1. Create User & Watchlist
        $user = User::factory()->create([
            'role' => 'TRADER',
            'company' => 'Solar Dynamics Corp',
        ]);

        $solarCode = HsCode::create([
            'code' => '85414300',
            'chapter' => '85',
            'description' => 'Solar cells assembled in modules',
            'unit' => 'NOS',
            'base_bcd_rate' => 25.0,
            'sws_rate' => 10.0,
            'igst_rate' => 12.0,
            'aidc_rate' => 0.0,
            'is_active' => true,
        ]);

        UserWatchlist::create([
            'user_id' => $user->id,
            'hs_code_id' => $solarCode->id,
            'baseline_fob_inr' => 2000000.0,
            'monthly_volume_units' => 4,
            'notify_on_increase_only' => true,
            'is_active' => true,
        ]);

        // 2. Ingest Circular
        $circular = RegulatoryCircular::create([
            'source' => 'CBIC_CUSTOMS',
            'notification_number' => '99/2026-Customs',
            'title' => 'Test Notification for Solar Cells',
            'issue_date' => now()->toDateString(),
            'effective_date' => now()->toDateString(),
            'parse_status' => 'PENDING',
            'raw_payload' => [
                'raw_text' => 'In the Table: against S. No. 426, for the entry "8541 43 00", in column (4), for the entry "25%", the entry "40%" shall be substituted; [Solar cells assembled into modules]',
            ],
        ]);

        // 3. Run Parser
        $parser = app(GazetteParserService::class);
        $revisions = $parser->parseCircular($circular);

        $this->assertCount(1, $revisions);
        $this->assertEquals('85414300', $revisions[0]->hsCode->code);
        $this->assertEquals(25.0, $revisions[0]->old_rate);
        $this->assertEquals(40.0, $revisions[0]->new_rate);
        $this->assertEquals('INCREASE', $revisions[0]->action);
        $this->assertEquals(15.0, $revisions[0]->delta);

        // 4. Verify Alert Generation
        $alert = TariffAlert::where('user_id', $user->id)->first();
        $this->assertNotNull($alert);
        $this->assertEquals($solarCode->id, $alert->hs_code_id);
        $this->assertEquals(15.0, $alert->delta);
        $this->assertGreaterThan(0, $alert->estimated_monthly_impact_inr);
    }

    public function test_calculator_api_endpoint_rate_limiting_and_calculation(): void
    {
        $response = $this->postJson('/api/v1/calculator/compare', [
            'unit_fob' => 100,
            'quantity' => 500,
            'currency' => 'USD',
            'old_bcd' => 10,
            'new_bcd' => 20,
            'sws_rate' => 10,
            'igst_rate' => 18,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.delta.action', 'INCREASE');
        $response->assertJsonPath('data.delta.bcd_rate_delta', 10);
    }
}
