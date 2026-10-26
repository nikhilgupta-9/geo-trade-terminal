<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HsCode;
use App\Models\RegulatoryCircular;
use App\Models\TariffRevision;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GazetteParserService
{
    public function __construct(
        protected TariffAlertDispatcher $alertDispatcher
    ) {}

    /**
     * Parse a regulatory circular and record tariff revisions.
     *
     * @return list<TariffRevision>
     */
    public function parseCircular(RegulatoryCircular $circular): array
    {
        $circular->update(['parse_status' => 'PROCESSING']);

        try {
            $rawText = $circular->raw_payload['raw_text'] ?? '';
            if (empty($rawText) && $circular->local_pdf_path) {
                // If text is in file storage
                $rawText = file_exists(storage_path('app/' . $circular->local_pdf_path))
                    ? file_get_contents(storage_path('app/' . $circular->local_pdf_path))
                    : '';
            }

            if (empty($rawText)) {
                throw new Exception('No raw gazette text or document content available for parsing.');
            }

            $extractedEntries = $this->extractTariffDeltasFromText($rawText, $circular->source);

            if (empty($extractedEntries)) {
                // Fallback attempt: line-by-line inspection
                $extractedEntries = $this->fallbackExtraction($rawText);
            }

            $revisions = [];

            DB::transaction(function () use ($circular, $extractedEntries, &$revisions) {
                foreach ($extractedEntries as $entry) {
                    $cleanCode = preg_replace('/\s+/', '', $entry['code']);
                    $chapter = substr($cleanCode, 0, 2);

                    // Find or create HS Code master entry
                    $hsCode = HsCode::firstOrCreate(
                        ['code' => $cleanCode],
                        [
                            'chapter' => $chapter,
                            'description' => $entry['description'] ?? "Goods under Tariff Item {$cleanCode}",
                            'unit' => 'KGS',
                            'base_bcd_rate' => $entry['old_rate'],
                            'sws_rate' => 10.00,
                            'igst_rate' => 18.00,
                            'aidc_rate' => 0.00,
                            'is_active' => true,
                        ]
                    );

                    $action = 'UNCHANGED';
                    if ((float)$entry['new_rate'] === 0.0 && (float)$entry['old_rate'] > 0.0) {
                        $action = 'EXEMPTED';
                    } elseif ((float)$entry['new_rate'] > (float)$entry['old_rate']) {
                        $action = 'INCREASE';
                    } elseif ((float)$entry['new_rate'] < (float)$entry['old_rate']) {
                        $action = 'DECREASE';
                    }

                    // Create or update revision record
                    $revision = TariffRevision::updateOrCreate(
                        [
                            'circular_id' => $circular->id,
                            'hs_code_id' => $hsCode->id,
                            'duty_type' => $entry['duty_type'] ?? 'BCD',
                        ],
                        [
                            'old_rate' => $entry['old_rate'],
                            'new_rate' => $entry['new_rate'],
                            'action' => $action,
                            'condition_notes' => $entry['notes'] ?? $entry['description'] ?? null,
                            'effective_from' => $circular->effective_date,
                        ]
                    );

                    $revision->refresh();

                    // Update HS Code's base BCD rate to the new revised rate
                    if (($entry['duty_type'] ?? 'BCD') === 'BCD') {
                        $hsCode->update(['base_bcd_rate' => $entry['new_rate']]);
                    }

                    $revisions[] = $revision;

                    // Broadcast live WebSocket ticker event
                    event(new \App\Events\LiveTariffTickerEvent($revision));

                    // Dispatch alerts to affected users
                    $this->alertDispatcher->dispatchForRevision($revision);
                }

                $payload = $circular->raw_payload ?? [];
                $payload['parsed_at'] = Carbon::now()->toIso8601String();
                $payload['extracted_items_count'] = count($revisions);
                $payload['items'] = $extractedEntries;

                $circular->update([
                    'parse_status' => 'PARSED',
                    'raw_payload' => $payload,
                    'failure_reason' => null,
                ]);
            });

            return $revisions;
        } catch (Exception $e) {
            Log::channel('single')->error("Failed parsing circular {$circular->id}: " . $e->getMessage());
            $circular->update([
                'parse_status' => 'FAILED',
                'failure_reason' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Regex and heuristic extraction engine for Indian Gazette formats.
     *
     * @return list<array<string, mixed>>
     */
    public function extractTariffDeltasFromText(string $text, string $source = 'CBIC_CUSTOMS'): array
    {
        $items = [];
        $isAdd = str_contains($text, 'anti-dumping') || str_contains($text, '(ADD)');

        // Pattern 1: CBIC Amending notification style
        // for the entry "8541 43 00", in column (4), for the entry "25%", the entry "40%" shall be substituted; [description]
        $pattern1 = '/(?:entry\s*")([0-9]{4}\s*[0-9]{2}\s*[0-9]{2,4})(?:".*?entry\s*")([0-9.]+)%(?:".*?entry\s*")([0-9.]+)%(?:".*?(?:\[(.*?)\])?)?/is';
        if (preg_match_all($pattern1, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $items[] = [
                    'code' => preg_replace('/\s+/', '', $m[1]),
                    'old_rate' => (float) $m[2],
                    'new_rate' => (float) $m[3],
                    'duty_type' => $isAdd ? 'ADD' : 'BCD',
                    'description' => isset($m[4]) && trim($m[4]) !== '' ? trim($m[4]) : null,
                    'notes' => 'Substituted via Gazette amending schedule',
                ];
            }
        }

        // Pattern 2: DGFT / Notification standard style
        // HS Code 8517 62 90: [description] Current BCD rate: 20.0%, New revised BCD rate: 10.0%
        // or HS Code 2902 43 00: [p-Xylene] Old BCD 0.0%, New BCD 2.5%.
        $pattern2 = '/(?:HS\s*Code|Tariff\s*item)\s*([0-9]{4}\s*[0-9]{2}\s*[0-9]{2,4}):?\s*(?:\[(.*?)\])?.*?(?:Current|Old)(?:\s*(?:BCD|rate))?:?\s*([0-9.]+)%.*?New(?:\s*(?:revised\s*BCD|BCD|rate))?:?\s*([0-9.]+)%/is';
        if (preg_match_all($pattern2, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $code = preg_replace('/\s+/', '', $m[1]);
                // avoid duplicate if already matched by pattern 1
                if (!collect($items)->contains('code', $code)) {
                    $items[] = [
                        'code' => $code,
                        'old_rate' => (float) $m[3],
                        'new_rate' => (float) $m[4],
                        'duty_type' => $isAdd ? 'ADD' : 'BCD',
                        'description' => isset($m[2]) && trim($m[2]) !== '' ? trim($m[2]) : null,
                        'notes' => 'Extracted from Trade Notice policy revision',
                    ];
                }
            }
        }

        // Pattern 3: Anti-dumping or simple duty delta statements
        // Tariff item 3907 61 00: Old rate BCD 10.0%, ADD imposed bringing effective duty to 18.5%.
        $pattern3 = '/(?:Tariff\s*item|item)\s*([0-9]{4}\s*[0-9]{2}\s*[0-9]{2,4}):?\s*Old\s*rate(?:\s*BCD)?\s*([0-9.]+)%.*?(?:New\s*rate|effective\s*duty\s*to)\s*([0-9.]+)%/is';
        if (preg_match_all($pattern3, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $code = preg_replace('/\s+/', '', $m[1]);
                if (!collect($items)->contains('code', $code)) {
                    $items[] = [
                        'code' => $code,
                        'old_rate' => (float) $m[2],
                        'new_rate' => (float) $m[3],
                        'duty_type' => $isAdd ? 'ADD' : 'BCD',
                        'description' => 'Polymer & Petrochemical notification item',
                        'notes' => 'Anti-dumping duty revision',
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Fallback extractor looking for 6-8 digit HS codes and percentage pairs.
     *
     * @return list<array<string, mixed>>
     */
    protected function fallbackExtraction(string $text): array
    {
        $items = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            if (preg_match('/\b([0-9]{4}\s*[0-9]{2}\s*[0-9]{2,4})\b/', $line, $codeMatch)) {
                if (preg_match_all('/([0-9]+(?:\.[0-9]+)?)\s*%/', $line, $pctMatches)) {
                    if (count($pctMatches[1]) >= 2) {
                        $items[] = [
                            'code' => preg_replace('/\s+/', '', $codeMatch[1]),
                            'old_rate' => (float) $pctMatches[1][0],
                            'new_rate' => (float) $pctMatches[1][1],
                            'duty_type' => 'BCD',
                            'description' => trim(strip_tags($line)),
                            'notes' => 'Fallback line extraction',
                        ];
                    }
                }
            }
        }

        return $items;
    }
}
