<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RegulatoryCircular;
use App\Services\GazetteParserService;
use Illuminate\Console\Command;

class ParseCircularCommand extends Command
{
    protected $signature = 'vectis:parse-circular {id? : Specific circular ID to re-parse}';
    protected $description = 'Trigger parser on pending or specific regulatory circulars';

    public function handle(GazetteParserService $parser): int
    {
        $id = $this->argument('id');

        if ($id) {
            $circular = RegulatoryCircular::find($id);
            if (!$circular) {
                $this->error("Circular #{$id} not found.");
                return self::FAILURE;
            }
            $this->info("Parsing Circular #{$circular->id}: {$circular->notification_number}...");
            $revisions = $parser->parseCircular($circular);
            $this->info("Extracted " . count($revisions) . " tariff revisions.");
            return self::SUCCESS;
        }

        $pending = RegulatoryCircular::whereIn('parse_status', ['PENDING', 'FAILED'])->get();
        $this->info("Found {$pending->count()} pending circulars.");

        foreach ($pending as $circular) {
            $this->line("Parsing Circular #{$circular->id}: {$circular->notification_number}...");
            $revisions = $parser->parseCircular($circular);
            $this->info("-> Extracted " . count($revisions) . " tariff revisions.");
        }

        return self::SUCCESS;
    }
}
