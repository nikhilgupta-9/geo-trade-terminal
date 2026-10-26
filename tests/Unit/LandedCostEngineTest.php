<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LandedCostEngine;
use PHPUnit\Framework\TestCase;

class LandedCostEngineTest extends TestCase
{
    protected LandedCostEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new LandedCostEngine();
    }

    public function test_deterministic_valuation_calculation(): void
    {
        // 100 units at $10.00 each = $1,000 FOB.
        // Exchange rate = 86.50 -> FOB INR = 86,500.
        // Freight = 20% = 17,300; Insurance = 1.125% = 973.125.
        // CIF = 86,500 + 17,300 + 973.125 = 104,773.125.
        // BCD = 10% = 10,477.3125.
        // SWS = 10% of BCD = 1,047.73125.
        // IGST Base = CIF + BCD + SWS = 116,298.16875.
        // IGST = 18% = 20,933.670375.
        // Total Duty = BCD + SWS + IGST = 32,458.71.
        $calc = $this->engine->calculate(
            unitFobUnit: 10.0,
            quantity: 100,
            currency: 'USD',
            bcdRate: 10.0,
            swsRate: 10.0,
            igstRate: 18.0,
            aidcRate: 0.0
        );

        $this->assertEquals(86500.0, $calc['total_fob_inr']);
        $this->assertEquals(104773.13, $calc['cif_inr']);
        $this->assertEquals(10477.31, $calc['duties']['bcd_inr']);
        $this->assertEquals(1047.73, $calc['duties']['sws_inr']);
        $this->assertEquals(20933.67, $calc['duties']['igst_inr']);
        $this->assertEquals(32458.71, $calc['duties']['total_duty_inr']);
        $this->assertEquals(137231.84, $calc['total_landed_cost_inr']);
        $this->assertEquals(1372.32, $calc['landed_cost_per_unit_inr']);
    }

    public function test_tariff_comparison_and_margin_erosion(): void
    {
        // Compare Old 25% BCD vs New 40% BCD on $180 unit FOB (Solar PV modules)
        $comparison = $this->engine->compare(
            unitFobUnit: 180.0,
            quantity: 1000,
            currency: 'USD',
            oldRates: ['bcd' => 25.0, 'sws' => 10.0, 'igst' => 12.0],
            newRates: ['bcd' => 40.0, 'sws' => 10.0, 'igst' => 12.0],
            sellingPricePerUnit: 25000.0
        );

        $this->assertEquals('INCREASE', $comparison['delta']['action']);
        $this->assertEquals(15.0, $comparison['delta']['bcd_rate_delta']);
        $this->assertGreaterThan(0, $comparison['delta']['total_duty_delta_inr']);
        $this->assertGreaterThan(0, $comparison['delta']['total_landed_cost_delta_inr']);
        $this->assertNotNull($comparison['delta']['margin_erosion_percent']);
        // Margin must decrease under tariff hike
        $this->assertLessThan(0, $comparison['delta']['margin_erosion_percent']);
    }
}
