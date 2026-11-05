<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

try {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? $_POST;

    $fobValue = (float) ($input['fob_value'] ?? 0);
    $freight = (float) ($input['freight'] ?? 0);
    $insurance = (float) ($input['insurance'] ?? 0);
    $currencyRate = (float) ($input['currency_rate'] ?? 84.50); // USD to INR default

    if ($fobValue <= 0) {
        json_error('FOB value must be greater than zero.', 422, 'INVALID_INPUT');
    }

    $bcdRate = (float) ($input['bcd_rate'] ?? 10.0);
    $swsRate = (float) ($input['sws_rate'] ?? 10.0);
    $igstRate = (float) ($input['igst_rate'] ?? 18.0);
    $aidcRate = (float) ($input['aidc_rate'] ?? 0.0);
    $oldBcdRate = isset($input['old_bcd_rate']) ? (float) $input['old_bcd_rate'] : null;

    $cifForeign = $fobValue + $freight + $insurance;
    $cifInr = round($cifForeign * $currencyRate, 2);

    // Current Valuation
    $bcdAmount = round($cifInr * ($bcdRate / 100), 2);
    $swsAmount = round($bcdAmount * ($swsRate / 100), 2);
    $aidcAmount = round($cifInr * ($aidcRate / 100), 2);
    $igstBase = $cifInr + $bcdAmount + $swsAmount + $aidcAmount;
    $igstAmount = round($igstBase * ($igstRate / 100), 2);
    $totalDuty = round($bcdAmount + $swsAmount + $aidcAmount + $igstAmount, 2);
    $landedCost = round($cifInr + $totalDuty, 2);
    $effectiveDutyPct = $cifInr > 0 ? round(($totalDuty / $cifInr) * 100, 2) : 0;

    $deltaAnalysis = null;
    if ($oldBcdRate !== null) {
        $oldBcdAmount = round($cifInr * ($oldBcdRate / 100), 2);
        $oldSwsAmount = round($oldBcdAmount * ($swsRate / 100), 2);
        $oldAidcAmount = round($cifInr * ($aidcRate / 100), 2);
        $oldIgstBase = $cifInr + $oldBcdAmount + $oldSwsAmount + $oldAidcAmount;
        $oldIgstAmount = round($oldIgstBase * ($igstRate / 100), 2);
        $oldTotalDuty = round($oldBcdAmount + $oldSwsAmount + $oldAidcAmount + $oldIgstAmount, 2);
        $oldLandedCost = round($cifInr + $oldTotalDuty, 2);

        $dutyDelta = round($totalDuty - $oldTotalDuty, 2);
        $marginImpactPct = $oldLandedCost > 0 ? round(($dutyDelta / $oldLandedCost) * 100, 2) : 0;

        $deltaAnalysis = [
            'old_bcd_rate' => $oldBcdRate,
            'old_total_duty_inr' => $oldTotalDuty,
            'old_landed_cost_inr' => $oldLandedCost,
            'duty_delta_inr' => $dutyDelta,
            'duty_delta_usd' => round($dutyDelta / $currencyRate, 2),
            'margin_impact_pct' => $marginImpactPct,
            'impact_type' => $dutyDelta > 0 ? 'DUTY_HIKE_COST_INCREASE' : ($dutyDelta < 0 ? 'DUTY_CUT_SAVINGS' : 'NEUTRAL'),
        ];
    }

    json_response([
        'status' => 'ok',
        'cif_foreign' => $cifForeign,
        'cif_inr' => $cifInr,
        'currency_rate' => $currencyRate,
        'breakdown' => [
            'bcd' => ['rate' => $bcdRate, 'amount_inr' => $bcdAmount],
            'sws' => ['rate' => $swsRate, 'amount_inr' => $swsAmount],
            'aidc' => ['rate' => $aidcRate, 'amount_inr' => $aidcAmount],
            'igst' => ['rate' => $igstRate, 'base_inr' => $igstBase, 'amount_inr' => $igstAmount],
        ],
        'total_duty_inr' => $totalDuty,
        'total_duty_usd' => round($totalDuty / $currencyRate, 2),
        'landed_cost_inr' => $landedCost,
        'landed_cost_usd' => round($landedCost / $currencyRate, 2),
        'effective_duty_pct' => $effectiveDutyPct,
        'delta_analysis' => $deltaAnalysis,
    ]);
} catch (Throwable $e) {
    json_error('Calculator error: ' . $e->getMessage(), 500, 'CALCULATOR_FAILED');
}
