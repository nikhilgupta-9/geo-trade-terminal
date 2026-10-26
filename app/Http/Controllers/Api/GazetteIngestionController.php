<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegulatoryCircular;
use App\Services\GazetteParserService;
use App\Services\GazetteScraperService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GazetteIngestionController extends Controller
{
    public function __construct(
        protected GazetteScraperService $scraper,
        protected GazetteParserService $parser
    ) {}

    public function index(Request $request): JsonResponse
    {
        $circulars = RegulatoryCircular::withCount('revisions')
            ->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $circulars->count(),
            'data' => $circulars,
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $circulars = $this->scraper->pollAll();
        $parsedCount = 0;

        foreach ($circulars as $circular) {
            if ($circular->parse_status === 'PENDING' || $circular->parse_status === 'FAILED') {
                $this->parser->parseCircular($circular);
                $parsedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Polled feeds: Ingested " . count($circulars) . " gazettes, parsed {$parsedCount}.",
            'count' => count($circulars),
        ]);
    }

    public function simulateGazette(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => 'required|in:CBIC_CUSTOMS,DGFT,MINISTRY_COMMERCE',
            'notification_number' => 'required|string|max:100',
            'title' => 'required|string|max:500',
            'raw_text' => 'required|string|min:20',
            'effective_date' => 'nullable|date',
        ]);

        $effectiveDate = !empty($validated['effective_date'])
            ? Carbon::parse($validated['effective_date'])->format('Y-m-d')
            : Carbon::now()->addDays(2)->format('Y-m-d');

        $circular = RegulatoryCircular::updateOrCreate(
            [
                'source' => $validated['source'],
                'notification_number' => $validated['notification_number'],
            ],
            [
                'title' => $validated['title'],
                'gazette_url' => 'https://egazette.gov.in/simulation/' . urlencode($validated['notification_number']),
                'issue_date' => Carbon::now()->format('Y-m-d'),
                'effective_date' => $effectiveDate,
                'parse_status' => 'PENDING',
                'raw_payload' => [
                    'raw_text' => $validated['raw_text'],
                    'simulated' => true,
                    'submitted_at' => Carbon::now()->toIso8601String(),
                ],
            ]
        );

        $revisions = $this->parser->parseCircular($circular);

        return response()->json([
            'success' => true,
            'message' => "Gazette circular processed! Generated " . count($revisions) . " tariff revisions.",
            'circular' => $circular->load('revisions.hsCode'),
            'revisions_count' => count($revisions),
        ]);
    }

    public function reparse(RegulatoryCircular $circular): JsonResponse
    {
        $revisions = $this->parser->parseCircular($circular);

        return response()->json([
            'success' => true,
            'message' => "Reparsed circular: Extracted " . count($revisions) . " tariff revisions.",
            'circular' => $circular->load('revisions.hsCode'),
            'revisions_count' => count($revisions),
        ]);
    }
}
