<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GazetteParserService;
use App\Services\GazetteScraperService;
use Illuminate\Console\Command;

class PollGazetteNotifications extends Command
{
    protected $signature = 'vectis:poll-gazette {--parse : Automatically parse ingested notifications}';
    protected $description = 'Poll CBIC and DGFT for new regulatory circulars, parse tariff revisions, and dispatch alerts';

    public function handle(GazetteScraperService $scraper, GazetteParserService $parser): int
    {
        $this->info('Starting Gazette Ingestion Pipeline (CBIC & DGFT)...');

        $circulars = $scraper->pollAll();
        $this->info("Ingested/Found " . count($circulars) . " circulars.");

        if ($this->option('parse') || true) {
            foreach ($circulars as $circular) {
                if ($circular->parse_status === 'PENDING' || $circular->parse_status === 'FAILED') {
                    $this->line("Parsing Circular: {$circular->notification_number} ({$circular->source})...");
                    $revisions = $parser->parseCircular($circular);
                    $this->info("-> Extracted " . count($revisions) . " tariff revisions.");
                }
            }
        }

        $this->info('Gazette Ingestion & Parsing completed successfully.');
        return self::SUCCESS;
    }
}
