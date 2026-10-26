<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HsCode;
use InvalidArgumentException;

/**
 * Institutional-grade Customs Landed Cost & Valuation Engine
 * Compliant with Indian Customs Valuation Rules (CBIC / Customs Act, 1962).
 */
class LandedCostEngine
{
    /**
     * Standard CBIC notified reference exchange rates against INR.
     */
    public const DEFAULT_EXCHANGE_RATES = [
        'INR' => 1.0,
        'USD' => 86.50,
        'EUR' => 93.20,
        'CNY' => 11.90,
        'JPY' => 0.58,
        'GBP' => 110.40,
        'AED' => 23.55,
        'SGD' => 64.80,
    ];

    /**
     * Calculate Landed Cost and duty breakdown for a single duty structure.
     *
     * @param float $unitFobUnit Unit FOB Price in specified currency
     * @param int|float $quantity Total units/quantity
     * @param string $currency Currency code (e.g. 'USD', 'INR', 'EUR')
     * @param float $bcdRate Basic Customs Duty percentage
     * @param float $swsRate Social Welfare Surcharge percentage (default 10% on BCD)
     * @param float $igstRate Integrated GST percentage (default 18%)
     * @param float $aidcRate AIDC percentage (default 0%)
     * @param float|null $freightTotal In currency, or null to default (20% of FOB if null and freightRate 0)
     * @param float|null $insuranceTotal In currency, or null to default (1.125% of FOB)
     * @param float $landingChargeRate In % (e.g. 1.0% landing charges or 0 if deprecated)
     * @param float|null $customExchangeRate Optional manual exchange rate override
     * @param float|null $targetSellingPricePerUnit In INR to evaluate gross margin
     * @return array<string, mixed>
     */
    public function calculate(
        float $unitFobUnit,
        int|float $quantity,
        string $currency = 'USD',
        float $bcdRate = 10.0,
        float $swsRate = 10.0,
        float $igstRate = 18.0,
        float $aidcRate = 0.0,
        ?float $freightTotal = null,
        ?float $insuranceTotal = null,
        float $landingChargeRate = 0.0,
        ?float $customExchangeRate = null,
        ?float $targetSellingPricePerUnit = null
    ): array {
        if ($unitFobUnit < 0 || $quantity <= 0) {
            throw new InvalidArgumentException('FOB unit cost must be non-negative and quantity must be positive.');
        }

        $currency = strtoupper($currency);
        $exchangeRate = $customExchangeRate ?? self::DEFAULT_EXCHANGE_RATES[$currency] ?? 1.0;

        // 1. FOB in Foreign Currency & INR
        $totalFobForeign = $unitFobUnit * $quantity;
        $totalFobInr = $totalFobForeign * $exchangeRate;

        // 2. Freight & Insurance (Rule 10(2) Customs Valuation Rules)
        // If not specified, default to 20% freight and 1.125% insurance of FOB
        $freightForeign = $freightTotal !== null ? $freightTotal : ($totalFobForeign * 0.20);
        $insuranceForeign = $insuranceTotal !== null ? $insuranceTotal : ($totalFobForeign * 0.01125);

        $freightInr = $freightForeign * $exchangeRate;
        $insuranceInr = $insuranceForeign * $exchangeRate;

        // 3. Assessable Value (CIF) in INR
        $cifInr = $totalFobInr + $freightInr + $insuranceInr;

        // 4. BCD (Basic Customs Duty) = CIF * BCD Rate %
        $bcdInr = $cifInr * ($bcdRate / 100.0);

        // 5. AIDC (Agriculture Infrastructure & Development Cess) = CIF * AIDC Rate %
        $aidcInr = $cifInr * ($aidcRate / 100.0);

        // 6. SWS (Social Welfare Surcharge) = (BCD + AIDC) * SWS Rate % (typically 10% of BCD)
        $swsInr = ($bcdInr + $aidcInr) * ($swsRate / 100.0);

        // 7. IGST = (CIF + BCD + AIDC + SWS) * IGST Rate %
        $igstBaseInr = $cifInr + $bcdInr + $aidcInr + $swsInr;
        $igstInr = $igstBaseInr * ($igstRate / 100.0);

        // 8. Total Duty Payables
        $totalDutyInr = $bcdInr + $aidcInr + $swsInr + $igstInr;
        $effectiveDutyPercent = $cifInr > 0 ? ($totalDutyInr / $cifInr) * 100.0 : 0.0;

        // 9. Landing / Port & CHA Handling Charges
        $landingChargesInr = $cifInr * ($landingChargeRate / 100.0);

        // 10. Total Landed Cost
        $totalLandedCostInr = $cifInr + $totalDutyInr + $landingChargesInr;
        $landedCostPerUnitInr = $quantity > 0 ? $totalLandedCostInr / $quantity : 0.0;

        // 11. Gross Margin Analysis (if selling price provided)
        $marginAnalysis = null;
        if ($targetSellingPricePerUnit !== null && $targetSellingPricePerUnit > 0) {
            $totalRevenueInr = $targetSellingPricePerUnit * $quantity;
            $grossProfitInr = $totalRevenueInr - $totalLandedCostInr;
            $grossMarginPercent = ($grossProfitInr / $totalRevenueInr) * 100.0;
            $unitMarginInr = $targetSellingPricePerUnit - $landedCostPerUnitInr;

            $marginAnalysis = [
                'selling_price_per_unit_inr' => round($targetSellingPricePerUnit, 2),
                'total_revenue_inr' => round($totalRevenueInr, 2),
                'gross_profit_inr' => round($grossProfitInr, 2),
                'gross_margin_percent' => round($grossMarginPercent, 2),
                'unit_margin_inr' => round($unitMarginInr, 2),
            ];
        }

        return [
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'quantity' => $quantity,
            'unit_fob_foreign' => round($unitFobUnit, 2),
            'total_fob_foreign' => round($totalFobForeign, 2),
            'total_fob_inr' => round($totalFobInr, 2),
            'freight_inr' => round($freightInr, 2),
            'insurance_inr' => round($insuranceInr, 2),
            'cif_inr' => round($cifInr, 2),
            'rates' => [
                'bcd' => $bcdRate,
                'sws' => $swsRate,
                'aidc' => $aidcRate,
                'igst' => $igstRate,
                'landing_charge' => $landingChargeRate,
            ],
            'duties' => [
                'bcd_inr' => round($bcdInr, 2),
                'aidc_inr' => round($aidcInr, 2),
                'sws_inr' => round($swsInr, 2),
                'igst_inr' => round($igstInr, 2),
                'total_duty_inr' => round($totalDutyInr, 2),
                'effective_duty_percent' => round($effectiveDutyPercent, 2),
            ],
            'landing_charges_inr' => round($landingChargesInr, 2),
            'total_landed_cost_inr' => round($totalLandedCostInr, 2),
            'landed_cost_per_unit_inr' => round($landedCostPerUnitInr, 2),
            'margin_analysis' => $marginAnalysis,
        ];
    }

    /**
     * Compare Old Tariff vs New Tariff and calculate the delta variance and margin erosion.
     *
     * @param float $unitFobUnit
     * @param int|float $quantity
     * @param string $currency
     * @param array{bcd: float, sws?: float, igst?: float, aidc?: float} $oldRates
     * @param array{bcd: float, sws?: float, igst?: float, aidc?: float} $newRates
     * @param float|null $freightTotal
     * @param float|null $insuranceTotal
     * @param float|null $sellingPricePerUnit
     * @return array<string, mixed>
     */
    public function compare(
        float $unitFobUnit,
        int|float $quantity,
        string $currency,
        array $oldRates,
        array $newRates,
        ?float $freightTotal = null,
        ?float $insuranceTotal = null,
        ?float $sellingPricePerUnit = null,
        ?float $customExchangeRate = null
    ): array {
        $oldCalc = $this->calculate(
            unitFobUnit: $unitFobUnit,
            quantity: $quantity,
            currency: $currency,
            bcdRate: $oldRates['bcd'] ?? 0.0,
            swsRate: $oldRates['sws'] ?? 10.0,
            igstRate: $oldRates['igst'] ?? 18.0,
            aidcRate: $oldRates['aidc'] ?? 0.0,
            freightTotal: $freightTotal,
            insuranceTotal: $insuranceTotal,
            customExchangeRate: $customExchangeRate,
            targetSellingPricePerUnit: $sellingPricePerUnit
        );

        $newCalc = $this->calculate(
            unitFobUnit: $unitFobUnit,
            quantity: $quantity,
            currency: $currency,
            bcdRate: $newRates['bcd'] ?? 0.0,
            swsRate: $newRates['sws'] ?? 10.0,
            igstRate: $newRates['igst'] ?? 18.0,
            aidcRate: $newRates['aidc'] ?? 0.0,
            freightTotal: $freightTotal,
            insuranceTotal: $insuranceTotal,
            customExchangeRate: $customExchangeRate,
            targetSellingPricePerUnit: $sellingPricePerUnit
        );

        $dutyDeltaInr = $newCalc['duties']['total_duty_inr'] - $oldCalc['duties']['total_duty_inr'];
        $landedCostDeltaInr = $newCalc['total_landed_cost_inr'] - $oldCalc['total_landed_cost_inr'];
        $unitCostDeltaInr = $newCalc['landed_cost_per_unit_inr'] - $oldCalc['landed_cost_per_unit_inr'];
        $effectiveDutyDelta = $newCalc['duties']['effective_duty_percent'] - $oldCalc['duties']['effective_duty_percent'];
        $percentageCostChange = $oldCalc['total_landed_cost_inr'] > 0
            ? ($landedCostDeltaInr / $oldCalc['total_landed_cost_inr']) * 100.0
            : 0.0;

        $marginErosionPercent = null;
        if ($oldCalc['margin_analysis'] && $newCalc['margin_analysis']) {
            $marginErosionPercent = $newCalc['margin_analysis']['gross_margin_percent'] - $oldCalc['margin_analysis']['gross_margin_percent'];
        }

        return [
            'baseline' => $oldCalc,
            'revised' => $newCalc,
            'delta' => [
                'bcd_rate_delta' => round(($newRates['bcd'] ?? 0.0) - ($oldRates['bcd'] ?? 0.0), 2),
                'total_duty_delta_inr' => round($dutyDeltaInr, 2),
                'total_landed_cost_delta_inr' => round($landedCostDeltaInr, 2),
                'unit_cost_delta_inr' => round($unitCostDeltaInr, 2),
                'effective_duty_delta_percent' => round($effectiveDutyDelta, 2),
                'cost_percentage_change' => round($percentageCostChange, 2),
                'margin_erosion_percent' => $marginErosionPercent !== null ? round($marginErosionPercent, 2) : null,
                'action' => $dutyDeltaInr > 0 ? 'INCREASE' : ($dutyDeltaInr < 0 ? 'DECREASE' : 'UNCHANGED'),
            ],
        ];
    }

    /**
     * Compute impact on a tracked watchlist item given a tariff revision.
     */
    public function evaluateWatchlistImpact(HsCode $hsCode, float $baselineFobInr, int $monthlyVolume, float $oldBcd, float $newBcd): array
    {
        $comparison = $this->compare(
            unitFobUnit: $baselineFobInr,
            quantity: $monthlyVolume,
            currency: 'INR',
            oldRates: [
                'bcd' => $oldBcd,
                'sws' => $hsCode->sws_rate,
                'igst' => $hsCode->igst_rate,
                'aidc' => $hsCode->aidc_rate,
            ],
            newRates: [
                'bcd' => $newBcd,
                'sws' => $hsCode->sws_rate,
                'igst' => $hsCode->igst_rate,
                'aidc' => $hsCode->aidc_rate,
            ]
        );

        return [
            'monthly_financial_impact_inr' => $comparison['delta']['total_landed_cost_delta_inr'],
            'annual_financial_impact_inr' => round($comparison['delta']['total_landed_cost_delta_inr'] * 12, 2),
            'unit_cost_increase_inr' => $comparison['delta']['unit_cost_delta_inr'],
            'percentage_cost_shift' => $comparison['delta']['cost_percentage_change'],
            'action' => $comparison['delta']['action'],
            'comparison' => $comparison,
        ];
    }
}
