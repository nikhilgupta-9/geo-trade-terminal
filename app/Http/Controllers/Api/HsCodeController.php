<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HsCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HsCodeController extends Controller
{
    /**
     * Autocomplete and search HS Codes across priority chapters.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', $request->query('query', ''));
        $chapter = $request->query('chapter');

        $hsQuery = HsCode::query()->where('is_active', true);

        if ($chapter && $chapter !== 'ALL') {
            $hsQuery->where('chapter', $chapter);
        }

        if (!empty($query)) {
            $cleanTerm = preg_replace('/\s+/', '', (string) $query);
            $hsQuery->where(function ($q) use ($query, $cleanTerm) {
                $q->where('code', 'LIKE', "{$cleanTerm}%")
                  ->orWhere('code', 'LIKE', "%{$cleanTerm}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            });
        }

        $results = $hsQuery->with('latestRevision')
            ->orderBy('chapter')
            ->orderBy('code')
            ->limit(25)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $results->count(),
            'query' => $query,
            'data' => $results->map(function (HsCode $hs) {
                return [
                    'id' => $hs->id,
                    'code' => $hs->code,
                    'chapter' => $hs->chapter,
                    'description' => $hs->description,
                    'unit' => $hs->unit,
                    'rates' => [
                        'bcd' => $hs->base_bcd_rate,
                        'sws' => $hs->sws_rate,
                        'igst' => $hs->igst_rate,
                        'aidc' => $hs->aidc_rate,
                    ],
                    'latest_revision' => $hs->latestRevision ? [
                        'old_rate' => $hs->latestRevision->old_rate,
                        'new_rate' => $hs->latestRevision->new_rate,
                        'delta' => $hs->latestRevision->delta,
                        'action' => $hs->latestRevision->action,
                        'effective_from' => $hs->latestRevision->effective_from?->format('Y-m-d'),
                    ] : null,
                ];
            }),
        ]);
    }
}
