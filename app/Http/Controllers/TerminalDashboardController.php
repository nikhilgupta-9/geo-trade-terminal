<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HsCode;
use App\Models\RegulatoryCircular;
use App\Models\TariffAlert;
use App\Models\TariffRevision;
use App\Models\User;
use App\Models\UserWatchlist;
use App\Services\LandedCostEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TerminalDashboardController extends Controller
{
    public function __construct(
        protected LandedCostEngine $engine
    ) {}

    public function index(Request $request): View
    {
        // Default to Vikram Malhotra (Trader) or logged in user
        $userId = session('active_user_id');
        $user = $userId ? User::find($userId) : User::where('role', 'TRADER')->first() ?? User::first();

        if ($user && !Auth::check()) {
            Auth::login($user);
            session(['active_user_id' => $user->id]);
        }

        // Summary Statistics
        $totalHsCodes = HsCode::where('is_active', true)->count();
        $circularsCount = RegulatoryCircular::count();
        $hikesCount = TariffRevision::where('action', 'INCREASE')->count();
        $cutsCount = TariffRevision::where('action', 'DECREASE')->count();
        $exemptionsCount = TariffRevision::where('action', 'EXEMPTED')->count();

        // Calculate portfolio financial impact for this user
        $watchlists = UserWatchlist::where('user_id', $user?->id ?? 1)
            ->where('is_active', true)
            ->with(['hsCode.latestRevision'])
            ->get();

        $portfolioNetMonthlyImpact = 0.0;
        foreach ($watchlists as $wl) {
            $latestRev = $wl->hsCode?->latestRevision;
            if ($latestRev) {
                $impact = $this->engine->evaluateWatchlistImpact(
                    hsCode: $wl->hsCode,
                    baselineFobInr: (float) $wl->baseline_fob_inr,
                    monthlyVolume: (int) $wl->monthly_volume_units,
                    oldBcd: (float) $latestRev->old_rate,
                    newBcd: (float) $latestRev->new_rate
                );
                $portfolioNetMonthlyImpact += $impact['monthly_financial_impact_inr'];
            }
        }

        // Unread alerts count
        $unreadAlertsCount = TariffAlert::where('user_id', $user?->id ?? 1)
            ->where('status', 'UNREAD')
            ->count();

        // All users for quick-switch switcher
        $allUsers = User::all();

        // Top recent tariff revisions for initial ticker and table
        $recentRevisions = TariffRevision::with(['circular', 'hsCode'])
            ->orderBy('effective_from', 'desc')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        // All active HS Codes for quick selection in calculator
        $allHsCodes = HsCode::where('is_active', true)
            ->orderBy('chapter')
            ->orderBy('code')
            ->get(['id', 'code', 'chapter', 'description', 'base_bcd_rate', 'sws_rate', 'igst_rate', 'aidc_rate']);

        return view('terminal.index', [
            'currentUser' => $user,
            'allUsers' => $allUsers,
            'totalHsCodes' => $totalHsCodes,
            'circularsCount' => $circularsCount,
            'hikesCount' => $hikesCount,
            'cutsCount' => $cutsCount,
            'exemptionsCount' => $exemptionsCount,
            'portfolioNetMonthlyImpact' => $portfolioNetMonthlyImpact,
            'unreadAlertsCount' => $unreadAlertsCount,
            'recentRevisions' => $recentRevisions,
            'allHsCodes' => $allHsCodes,
            'exchangeRates' => LandedCostEngine::DEFAULT_EXCHANGE_RATES,
        ]);
    }

    public function switchUser(Request $request, int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);
        Auth::login($user);
        session(['active_user_id' => $user->id]);

        return redirect()->route('terminal.index')->with('status', "Switched role to {$user->name} ({$user->role})");
    }
}
