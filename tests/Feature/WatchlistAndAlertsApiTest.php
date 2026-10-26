<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HsCode;
use App\Models\RegulatoryCircular;
use App\Models\TariffAlert;
use App\Models\TariffRevision;
use App\Models\User;
use App\Models\UserWatchlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchlistAndAlertsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected HsCode $solar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'TRADER',
            'company' => 'Apex Global',
        ]);

        $this->solar = HsCode::create([
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
    }

    public function test_user_can_add_update_and_delete_watchlist_items(): void
    {
        $this->actingAs($this->user);
        session(['active_user_id' => $this->user->id]);

        // Add
        $res = $this->postJson('/api/v1/watchlist', [
            'hs_code' => '85414300',
            'baseline_fob_inr' => 2500000,
            'monthly_volume_units' => 8,
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $this->assertDatabaseHas('user_watchlists', [
            'user_id' => $this->user->id,
            'hs_code_id' => $this->solar->id,
            'monthly_volume_units' => 8,
        ]);

        $watchlist = UserWatchlist::where('user_id', $this->user->id)->first();

        // Update
        $updateRes = $this->putJson("/api/v1/watchlist/{$watchlist->id}", [
            'monthly_volume_units' => 12,
        ]);
        $updateRes->assertStatus(200);
        $this->assertEquals(12, $watchlist->fresh()->monthly_volume_units);

        // Delete
        $deleteRes = $this->deleteJson("/api/v1/watchlist/{$watchlist->id}");
        $deleteRes->assertStatus(200);
        $this->assertDatabaseMissing('user_watchlists', ['id' => $watchlist->id]);
    }

    public function test_alerts_read_and_dismiss_endpoints(): void
    {
        $this->actingAs($this->user);
        session(['active_user_id' => $this->user->id]);

        $circular = RegulatoryCircular::create([
            'source' => 'CBIC_CUSTOMS',
            'notification_number' => '50/2026-Customs',
            'title' => 'Amending Notification',
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
            'user_id' => $this->user->id,
            'hs_code_id' => $this->solar->id,
            'revision_id' => $revision->id,
            'old_rate' => 25.0,
            'new_rate' => 40.0,
            'delta' => 15.0,
            'estimated_monthly_impact_inr' => 4000000,
            'status' => 'UNREAD',
        ]);

        // Get alerts
        $getRes = $this->getJson('/api/v1/alerts');
        $getRes->assertStatus(200);
        $getRes->assertJsonPath('unread_count', 1);

        // Mark as read
        $readRes = $this->postJson("/api/v1/alerts/{$alert->id}/read");
        $readRes->assertStatus(200);
        $this->assertEquals('READ', $alert->fresh()->status);

        // Dismiss
        $dismissRes = $this->postJson("/api/v1/alerts/{$alert->id}/dismiss");
        $dismissRes->assertStatus(200);
        $this->assertEquals('DISMISSED', $alert->fresh()->status);
    }

    public function test_role_switch_controller(): void
    {
        $chaUser = User::factory()->create([
            'role' => 'CHA',
            'company' => 'Maritime Customs Brokers',
        ]);

        $response = $this->post("/switch-user/{$chaUser->id}");
        $response->assertRedirect('/');
        $this->assertEquals($chaUser->id, session('active_user_id'));
    }
}
