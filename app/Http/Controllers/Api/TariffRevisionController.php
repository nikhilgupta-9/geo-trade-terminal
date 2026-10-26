<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TariffRevision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TariffRevisionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TariffRevision::with(['circular', 'hsCode']);

        if ($request->filled('chapter') && $request->chapter !== 'ALL') {
            $chapter = $request->chapter;
            $query->whereHas('hsCode', function ($q) use ($chapter) {
                $q->where('chapter', $chapter);
            });
        }

        if ($request->filled('action') && $request->action !== 'ALL') {
            $query->where('action', $request->action);
        }

        if ($request->filled('source') && $request->source !== 'ALL') {
            $source = $request->source;
            $query->whereHas('circular', function ($q) use ($source) {
                $q->where('source', $source);
            });
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->whereHas('hsCode', function ($sq) use ($term) {
                    $sq->where('code', 'LIKE', "%{$term}%")
                       ->orWhere('description', 'LIKE', "%{$term}%");
                })->orWhereHas('circular', function ($sq) use ($term) {
                    $sq->where('notification_number', 'LIKE', "%{$term}%")
                       ->orWhere('title', 'LIKE', "%{$term}%");
                });
            });
        }

        $revisions = $query->orderBy('effective_from', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $revisions->count(),
            'data' => $revisions,
        ]);
    }

    public function show(TariffRevision $revision): JsonResponse
    {
        $revision->load(['circular', 'hsCode']);

        return response()->json([
            'success' => true,
            'data' => $revision,
        ]);
    }
}
