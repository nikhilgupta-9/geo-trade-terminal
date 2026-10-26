<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HsCode;
use App\Models\User;
use App\Models\UserWatchlist;
use App\Services\LandedCostEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    public function __construct(
        protected LandedCostEngine $engine
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->query('user_id') ?? session('active_user_id') ?? Auth::id() ?? 2;
        $user = User::find($userId) ?? User::first();

        $watchlists = UserWatchlist::where('user_id', $user->id)
            ->with(['hsCode.latestRevision.circular'])
            ->get();

        $items = $watchlists->map(function (UserWatchlist $item) {
            $hs = $item->hsCode;
            $rev = $hs?->latestRevision;

            $impact = null;
            if ($rev) {
                $impact = $this->engine->evaluateWatchlistImpact(
                    hsCode: $hs,
                    baselineFobInr: (float) $item->baseline_fob_inr,
                    monthlyVolume: (int) $item->monthly_volume_units,
                    oldBcd: (float) $rev->old_rate,
                    newBcd: (float) $rev->new_rate
                );
            }

            return [
                'id' => $item->id,
                'hs_code_id' => $item->hs_code_id,
                'code' => $hs->code,
                'chapter' => $hs->chapter,
                'description' => $hs->description,
                'current_bcd' => $hs->base_bcd_rate,
                'baseline_fob_inr' => $item->baseline_fob_inr,
                'monthly_volume_units' => $item->monthly_volume_units,
                'notify_on_increase_only' => $item->notify_on_increase_only,
                'is_active' => $item->is_active,
                'latest_revision' => $rev ? [
                    'id' => $rev->id,
                    'circular' => $rev->circular?->notification_number,
                    'old_rate' => $rev->old_rate,
                    'new_rate' => $rev->new_rate,
                    'delta' => $rev->delta,
                    'action' => $rev->action,
                    'effective_from' => $rev->effective_from->format('Y-m-d'),
                ] : null,
                'impact' => $impact ? [
                    'monthly_inr' => $impact['monthly_financial_impact_inr'],
                    'annual_inr' => $impact['annual_financial_impact_inr'],
                    'percentage_shift' => $impact['percentage_cost_shift'],
                    'action' => $impact['action'],
                ] : null,
            ];
        });

        $totalMonthlyExposure = $items->sum(fn ($i) => $i['impact']['monthly_inr'] ?? 0);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'company' => $user->company,
            ],
            'total_monthly_exposure_inr' => round($totalMonthlyExposure, 2),
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $userId = session('active_user_id', Auth::id() ?? 1);

        $validated = $request->validate([
            'hs_code' => 'required|string',
            'baseline_fob_inr' => 'nullable|numeric|min:0',
            'monthly_volume_units' => 'nullable|integer|min:1',
            'notify_on_increase_only' => 'nullable|boolean',
        ]);

        $cleanCode = preg_replace('/\s+/', '', $validated['hs_code']);
        $hsCode = HsCode::where('code', $cleanCode)->first();

        if (!$hsCode) {
            return response()->json([
                'success' => false,
                'message' => "HS Code {$cleanCode} does not exist in master table.",
            ], 422);
        }

        $watchlist = UserWatchlist::updateOrCreate(
            [
                'user_id' => $userId,
                'hs_code_id' => $hsCode->id,
            ],
            [
                'baseline_fob_inr' => $validated['baseline_fob_inr'] ?? 1000000.00,
                'monthly_volume_units' => $validated['monthly_volume_units'] ?? 5,
                'notify_on_increase_only' => $validated['notify_on_increase_only'] ?? false,
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Added HS Code {$hsCode->code} to watchlist.",
            'data' => $watchlist->load('hsCode'),
        ]);
    }

    public function update(Request $request, UserWatchlist $watchlist): JsonResponse
    {
        $validated = $request->validate([
            'baseline_fob_inr' => 'nullable|numeric|min:0',
            'monthly_volume_units' => 'nullable|integer|min:1',
            'notify_on_increase_only' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $watchlist->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Watchlist entry updated.',
            'data' => $watchlist,
        ]);
    }

    public function destroy(UserWatchlist $watchlist): JsonResponse
    {
        $code = $watchlist->hsCode?->code;
        $watchlist->delete();

        return response()->json([
            'success' => true,
            'message' => "Removed HS Code {$code} from watchlist.",
        ]);
    }

    /**
     * Delete watchlist item by ID or HS Code from body/query.
     */
    public function remove(Request $request): JsonResponse
    {
        $userId = $request->query('user_id') ?? session('active_user_id') ?? Auth::id() ?? 2;

        if ($request->filled('id')) {
            $watchlist = UserWatchlist::where('user_id', $userId)->where('id', $request->input('id'))->first();
        } elseif ($request->filled('hs_code')) {
            $cleanCode = preg_replace('/\s+/', '', (string) $request->input('hs_code'));
            $watchlist = UserWatchlist::where('user_id', $userId)
                ->whereHas('hsCode', function ($q) use ($cleanCode) {
                    $q->where('code', $cleanCode);
                })->first();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Specify either id or hs_code to remove.',
            ], 422);
        }

        if (!$watchlist) {
            return response()->json([
                'success' => false,
                'message' => 'Watchlist entry not found.',
            ], 404);
        }

        $code = $watchlist->hsCode?->code;
        $watchlist->delete();

        return response()->json([
            'success' => true,
            'message' => "Removed HS Code {$code} from watchlist.",
        ]);
    }
}
