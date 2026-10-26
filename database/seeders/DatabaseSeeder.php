<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HsCode;
use App\Models\User;
use App\Models\UserWatchlist;
use App\Services\GazetteParserService;
use App\Services\GazetteScraperService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Institutional Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@geotrade.terminal'],
            [
                'name' => 'Alexander Vance',
                'role' => 'ADMIN',
                'company' => 'Geo Trade Terminal HQ',
                'password' => Hash::make('password'),
            ]
        );

        $trader = User::firstOrCreate(
            ['email' => 'trader@geotrade.terminal'],
            [
                'name' => 'Vikram Malhotra',
                'role' => 'TRADER',
                'company' => 'Apex Global Commodities Ltd.',
                'password' => Hash::make('password'),
            ]
        );

        $cha = User::firstOrCreate(
            ['email' => 'cha@geotrade.terminal'],
            [
                'name' => 'Rajesh Nambiar',
                'role' => 'CHA',
                'company' => 'Maritime Customs Brokers LLP',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Seed Master HS Codes
        $hsCodeDataset = [
            // Chapter 85: Electronics, Solar, Semiconductors
            [
                'code' => '85414300',
                'chapter' => '85',
                'description' => 'Solar cells, assembled in modules or made up into panels (Photovoltaic PV Modules)',
                'unit' => 'NOS',
                'base_bcd_rate' => 25.00,
                'sws_rate' => 10.00,
                'igst_rate' => 12.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '85423100',
                'chapter' => '85',
                'description' => 'Electronic integrated circuits: Processors and controllers, logic circuits and memories',
                'unit' => 'NOS',
                'base_bcd_rate' => 7.50,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '85423200',
                'chapter' => '85',
                'description' => 'Electronic integrated circuits: Memories (DRAM, NAND Flash)',
                'unit' => 'NOS',
                'base_bcd_rate' => 7.50,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '85176290',
                'chapter' => '85',
                'description' => 'Machines for reception, conversion and transmission of voice, images or data (Routers, Switches)',
                'unit' => 'NOS',
                'base_bcd_rate' => 20.00,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '85076000',
                'chapter' => '85',
                'description' => 'Lithium-ion accumulators and battery packs for EV and renewable energy storage',
                'unit' => 'NOS',
                'base_bcd_rate' => 10.00,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 5.00,
            ],
            [
                'code' => '85044090',
                'chapter' => '85',
                'description' => 'Static converters, solar string inverters, and high-frequency power rectifiers',
                'unit' => 'NOS',
                'base_bcd_rate' => 15.00,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],

            // Chapter 84: Machinery & Computing
            [
                'code' => '84713010',
                'chapter' => '84',
                'description' => 'Portable automatic data processing machines (Laptops, Notebooks and Tablets)',
                'unit' => 'NOS',
                'base_bcd_rate' => 0.00,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '84715000',
                'chapter' => '84',
                'description' => 'Digital processing units other than portable (Enterprise servers and workstations)',
                'unit' => 'NOS',
                'base_bcd_rate' => 7.50,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '84862000',
                'chapter' => '84',
                'description' => 'Machines and apparatus for the manufacture of semiconductor boules or wafers',
                'unit' => 'NOS',
                'base_bcd_rate' => 0.00,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],

            // Chapter 28: Inorganic Chemicals & Critical Raw Materials
            [
                'code' => '28046900',
                'chapter' => '28',
                'description' => 'Silicon, other than containing by weight not less than 99.99% of silicon (Polysilicon feedstock)',
                'unit' => 'KGS',
                'base_bcd_rate' => 7.50,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '28252000',
                'chapter' => '28',
                'description' => 'Lithium oxide and lithium hydroxide (Battery precursor chemical grade)',
                'unit' => 'KGS',
                'base_bcd_rate' => 0.00,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '28362000',
                'chapter' => '28',
                'description' => 'Disodium carbonate (Dense and Light Soda Ash for glass & detergent manufacturing)',
                'unit' => 'KGS',
                'base_bcd_rate' => 7.50,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],

            // Chapter 29: Organic Chemicals & APIs
            [
                'code' => '29024300',
                'chapter' => '29',
                'description' => 'p-Xylene (Purified Terephthalic Acid intermediate)',
                'unit' => 'KGS',
                'base_bcd_rate' => 0.00,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '29051100',
                'chapter' => '29',
                'description' => 'Methanol (Methyl alcohol) industrial feedstock grade',
                'unit' => 'KGS',
                'base_bcd_rate' => 5.00,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '29152100',
                'chapter' => '29',
                'description' => 'Acetic acid (Glacial acetic acid 99.8% purity)',
                'unit' => 'KGS',
                'base_bcd_rate' => 7.50,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '29333990',
                'chapter' => '29',
                'description' => 'Heterocyclic compounds with nitrogen hetero-atom(s) only (Active Pharmaceutical Ingredients)',
                'unit' => 'KGS',
                'base_bcd_rate' => 10.00,
                'sws_rate' => 10.00,
                'igst_rate' => 12.00,
                'aidc_rate' => 0.00,
            ],

            // Chapter 39: Polymers & Resins
            [
                'code' => '39076100',
                'chapter' => '39',
                'description' => 'Polyethylene terephthalate (PET Resin) intrinsic viscosity >= 78 ml/g',
                'unit' => 'KGS',
                'base_bcd_rate' => 10.00,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
            [
                'code' => '39011010',
                'chapter' => '39',
                'description' => 'Linear low density polyethylene (LLDPE) virgin polymer granules',
                'unit' => 'KGS',
                'base_bcd_rate' => 7.50,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],

            // Chapter 72: Steel & Ferrous Metals
            [
                'code' => '72081000',
                'chapter' => '72',
                'description' => 'Flat-rolled products of iron or non-alloy steel, in coils, hot-rolled',
                'unit' => 'KGS',
                'base_bcd_rate' => 12.50,
                'sws_rate' => 10.00,
                'igst_rate' => 18.00,
                'aidc_rate' => 0.00,
            ],
        ];

        foreach ($hsCodeDataset as $item) {
            HsCode::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }

        // 3. Setup User Watchlists
        $solarCode = HsCode::where('code', '85414300')->first();
        $laptopCode = HsCode::where('code', '84713010')->first();
        $methanolCode = HsCode::where('code', '29051100')->first();
        $petCode = HsCode::where('code', '39076100')->first();
        $semiCode = HsCode::where('code', '85423100')->first();

        if ($solarCode) {
            UserWatchlist::firstOrCreate(
                ['user_id' => $trader->id, 'hs_code_id' => $solarCode->id],
                [
                    'baseline_fob_inr' => 2500000.00, // standard 40ft container baseline
                    'monthly_volume_units' => 8, // 8 containers/mo
                    'notify_on_increase_only' => false,
                    'is_active' => true,
                ]
            );

            UserWatchlist::firstOrCreate(
                ['user_id' => $cha->id, 'hs_code_id' => $solarCode->id],
                [
                    'baseline_fob_inr' => 2500000.00,
                    'monthly_volume_units' => 24,
                    'notify_on_increase_only' => true,
                    'is_active' => true,
                ]
            );
        }

        if ($laptopCode) {
            UserWatchlist::firstOrCreate(
                ['user_id' => $trader->id, 'hs_code_id' => $laptopCode->id],
                [
                    'baseline_fob_inr' => 1800000.00,
                    'monthly_volume_units' => 5,
                    'notify_on_increase_only' => false,
                    'is_active' => true,
                ]
            );
        }

        if ($methanolCode) {
            UserWatchlist::firstOrCreate(
                ['user_id' => $trader->id, 'hs_code_id' => $methanolCode->id],
                [
                    'baseline_fob_inr' => 3200000.00,
                    'monthly_volume_units' => 12,
                    'notify_on_increase_only' => false,
                    'is_active' => true,
                ]
            );
        }

        if ($petCode) {
            UserWatchlist::firstOrCreate(
                ['user_id' => $cha->id, 'hs_code_id' => $petCode->id],
                [
                    'baseline_fob_inr' => 1400000.00,
                    'monthly_volume_units' => 15,
                    'notify_on_increase_only' => true,
                    'is_active' => true,
                ]
            );
        }

        if ($semiCode) {
            UserWatchlist::firstOrCreate(
                ['user_id' => $trader->id, 'hs_code_id' => $semiCode->id],
                [
                    'baseline_fob_inr' => 4500000.00,
                    'monthly_volume_units' => 4,
                    'notify_on_increase_only' => false,
                    'is_active' => true,
                ]
            );
        }

        // 4. Run Scraper & Parser to ingest gazette notifications and trigger alerts!
        $scraper = app(GazetteScraperService::class);
        $parser = app(GazetteParserService::class);

        $circulars = $scraper->pollAll();
        foreach ($circulars as $circular) {
            $parser->parseCircular($circular);
        }
    }
}
