<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RegulatoryCircular;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GazetteScraperService
{
    /**
     * Poll and ingest recent regulatory circulars from CBIC and DGFT.
     *
     * @return list<RegulatoryCircular>
     */
    public function pollAll(): array
    {
        $ingested = [];

        // 1. Ingest CBIC Customs Tariff Notifications
        $cbicCirculars = $this->ingestCbicFeed();
        $ingested = array_merge($ingested, $cbicCirculars);

        // 2. Ingest DGFT Public Notices & Trade Policy circulars
        $dgftCirculars = $this->ingestDgftFeed();
        $ingested = array_merge($ingested, $dgftCirculars);

        return $ingested;
    }

    /**
     * Ingest CBIC Gazette Circulars (Customs Tariff amendments).
     *
     * @return list<RegulatoryCircular>
     */
    public function ingestCbicFeed(): array
    {
        // Sample gazette feeds from official Gazette of India / CBIC portal
        $mockNotifications = [
            [
                'source' => 'CBIC_CUSTOMS',
                'notification_number' => '14/2026-Customs',
                'title' => 'Amending Notification No. 50/2017-Customs to rationalize BCD on Electronic Components and Solar Cells',
                'gazette_url' => 'https://cbic.gov.in/resources//htdocs-cbec/customs/cs-act/notifications/notfns-2026/cs-tarr2026/cs14-2026.pdf',
                'issue_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'effective_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                'raw_text' => <<<TEXT
GOVERNMENT OF INDIA
MINISTRY OF FINANCE
(DEPARTMENT OF REVENUE)
Notification No. 14/2026-Customs
New Delhi, the 24th October, 2026

G.S.R. (E).— In exercise of the powers conferred by sub-section (1) of section 25 of the Customs Act, 1962 (52 of 1962), the Central Government, on being satisfied that it is necessary in the public interest so to do, hereby makes the following further amendments in the notification of the Government of India in the Ministry of Finance (Department of Revenue), No. 50/2017-Customs, dated the 30th June, 2017:

In the said notification, in the Table:
(i) against S. No. 426, in column (2), for the entry "8541 43 00", in column (4), for the entry "25%", the entry "40%" shall be substituted; [Solar cells, assembled in modules or made up into panels]
(ii) against S. No. 427, in column (2), for the entry "8542 31 00", in column (4), for the entry "7.5%", the entry "2.5%" shall be substituted; [Processors and controllers, whether or not combined with memories, converters, logic circuits]
(iii) against S. No. 512, in column (2), for the entry "8471 30 10", in column (4), for the entry "0%", the entry "15%" shall be substituted; [Personal computers, laptops and microcomputers]
(iv) against S. No. 189, in column (2), for the entry "2804 69 00", in column (4), for the entry "7.5%", the entry "10%" shall be substituted; [Silicon, other than containing by weight not less than 99.99% of silicon]
(v) against S. No. 201, in column (2), for the entry "2933 39 90", in column (4), for the entry "10%", the entry "5%" shall be substituted; [Heterocyclic compounds with nitrogen hetero-atom(s) only]

This notification shall come into force on the 27th day of October, 2026.
[F. No. 354/42/2026-TRU]
TEXT
            ],
            [
                'source' => 'CBIC_CUSTOMS',
                'notification_number' => '15/2026-Customs (ADD)',
                'title' => 'Imposition of Definitive Anti-Dumping Duty on imports of Virgin Polyethylene Terephthalate (PET Resin)',
                'gazette_url' => 'https://cbic.gov.in/resources//htdocs-cbec/customs/cs-act/notifications/notfns-2026/cs-add2026/csadd15-2026.pdf',
                'issue_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
                'effective_date' => Carbon::now()->format('Y-m-d'),
                'raw_text' => <<<TEXT
GOVERNMENT OF INDIA
MINISTRY OF FINANCE
(DEPARTMENT OF REVENUE)
Notification No. 15/2026-Customs (ADD)
New Delhi, the 25th October, 2026

G.S.R. 712(E).— Whereas, in the matter of "Virgin Polyethylene Terephthalate (PET Resin)" falling under tariff item 3907 61 00 or 3907 69 00, originating in or exported from the specified countries:
The Central Government hereby imposes anti-dumping duty:
Tariff item 3907 61 00: Old rate BCD 10.0%, ADD imposed bringing effective duty to 18.5%.
Tariff item 3907 69 00: Old rate 10.0%, New rate 18.5%.
Effective from 26th October, 2026.
TEXT
            ],
        ];

        return $this->storeOrRetrieveCirculars($mockNotifications);
    }

    /**
     * Ingest DGFT Public Notices and Trade Policy notifications.
     *
     * @return list<RegulatoryCircular>
     */
    public function ingestDgftFeed(): array
    {
        $mockNotifications = [
            [
                'source' => 'DGFT',
                'notification_number' => '38/2025-2026',
                'title' => 'Amendment in Import Policy Condition of items under Chapter 84 and 85 of ITC (HS), 2022',
                'gazette_url' => 'https://dgft.gov.in/CP/?opt=notification-38-2026',
                'issue_date' => Carbon::now()->subDays(4)->format('Y-m-d'),
                'effective_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'raw_text' => <<<TEXT
GOVERNMENT OF INDIA
MINISTRY OF COMMERCE AND INDUSTRY
DEPARTMENT OF COMMERCE
DIRECTORATE GENERAL OF FOREIGN TRADE
Notification No. 38/2025-2026
New Delhi, Dated: 22nd October, 2026

Subject: Amendment in Import Policy condition of items under ITC (HS) 8471 and 8517.
S.O.(E): In exercise of powers conferred by Section 3 and Section 5 of Foreign Trade (Development & Regulation) Act, 1992:
The Central Government revises import policy for:
HS Code 8517 62 90: [Machines for the reception, conversion and transmission or regeneration of voice, images or other data]
Current BCD rate: 20.0%, New revised BCD rate: 10.0% under bilateral FTA concessions.
HS Code 8471 50 00: [Processing units other than those of sub-headings 8471 41 or 8471 49]
Current rate: 7.5%, New rate: 12.5%.
Effective immediately.
TEXT
            ],
            [
                'source' => 'DGFT',
                'notification_number' => '41/2025-2026',
                'title' => 'Rationalization of Organic & Pharmaceutical Chemicals Export-Import Regime',
                'gazette_url' => 'https://dgft.gov.in/CP/?opt=notification-41-2026',
                'issue_date' => Carbon::now()->subHours(6)->format('Y-m-d'),
                'effective_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'raw_text' => <<<TEXT
GOVERNMENT OF INDIA
DIRECTORATE GENERAL OF FOREIGN TRADE
Notification No. 41/2025-2026
Dated: 26th October, 2026

Policy amendment regarding organic compounds under Chapter 29:
HS Code 2902 43 00: [p-Xylene] Old BCD 0.0%, New BCD 2.5%.
HS Code 2905 11 00: [Methanol (methyl alcohol)] Old BCD 5.0%, New BCD 7.5%.
HS Code 2915 21 00: [Acetic acid] Old BCD 7.5%, New BCD 10.0%.
Effective from 31st October, 2026.
TEXT
            ],
        ];

        return $this->storeOrRetrieveCirculars($mockNotifications);
    }

    /**
     * Store mock or live notifications into database and local structured storage.
     *
     * @param list<array<string, mixed>> $items
     * @return list<RegulatoryCircular>
     */
    protected function storeOrRetrieveCirculars(array $items): array
    {
        $records = [];

        foreach ($items as $item) {
            $circular = RegulatoryCircular::firstOrCreate(
                [
                    'source' => $item['source'],
                    'notification_number' => $item['notification_number'],
                ],
                [
                    'title' => $item['title'],
                    'gazette_url' => $item['gazette_url'],
                    'issue_date' => $item['issue_date'],
                    'effective_date' => $item['effective_date'],
                    'parse_status' => 'PENDING',
                    'raw_payload' => [
                        'raw_text' => $item['raw_text'],
                        'ingested_at' => Carbon::now()->toIso8601String(),
                    ],
                ]
            );

            // Save raw text to gazette storage file
            $filePath = 'gazettes/' . strtolower($item['source']) . '/' . str_replace('/', '_', $item['notification_number']) . '.txt';
            Storage::disk('local')->put($filePath, $item['raw_text']);
            $circular->update(['local_pdf_path' => $filePath]);

            $records[] = $circular;
        }

        return $records;
    }
}
