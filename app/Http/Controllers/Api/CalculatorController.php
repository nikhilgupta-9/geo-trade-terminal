<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HsCode;
use App\Services\LandedCostEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalculatorController extends Controller
{
    public function __construct(
        protected LandedCostEngine $engine
    ) {}

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unit_fob' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'bcd_rate' => 'nullable|numeric|min:0|max:150',
            'sws_rate' => 'nullable|numeric|min:0|max:100',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'aidc_rate' => 'nullable|numeric|min:0|max:100',
            'freight_total' => 'nullable|numeric|min:0',
            'insurance_total' => 'nullable|numeric|min:0',
            'landing_charge_rate' => 'nullable|numeric|min:0|max:20',
            'custom_exchange_rate' => 'nullable|numeric|min:0',
            'target_selling_price' => 'nullable|numeric|min:0',
        ]);

        $result = $this->engine->calculate(
            unitFobUnit: (float) $validated['unit_fob'],
            quantity: (float) $validated['quantity'],
            currency: $validated['currency'] ?? 'USD',
            bcdRate: (float) ($validated['bcd_rate'] ?? 10.0),
            swsRate: (float) ($validated['sws_rate'] ?? 10.0),
            igstRate: (float) ($validated['igst_rate'] ?? 18.0),
            aidcRate: (float) ($validated['aidc_rate'] ?? 0.0),
            freightTotal: isset($validated['freight_total']) ? (float) $validated['freight_total'] : null,
            insuranceTotal: isset($validated['insurance_total']) ? (float) $validated['insurance_total'] : null,
            landingChargeRate: (float) ($validated['landing_charge_rate'] ?? 0.0),
            customExchangeRate: isset($validated['custom_exchange_rate']) ? (float) $validated['custom_exchange_rate'] : null,
            targetSellingPricePerUnit: isset($validated['target_selling_price']) ? (float) $validated['target_selling_price'] : null
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unit_fob' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'old_bcd' => 'required|numeric|min:0|max:150',
            'new_bcd' => 'required|numeric|min:0|max:150',
            'sws_rate' => 'nullable|numeric|min:0|max:100',
            'igst_rate' => 'nullable|numeric|min:0|max:100',
            'aidc_rate' => 'nullable|numeric|min:0|max:100',
            'freight_total' => 'nullable|numeric|min:0',
            'insurance_total' => 'nullable|numeric|min:0',
            'target_selling_price' => 'nullable|numeric|min:0',
            'custom_exchange_rate' => 'nullable|numeric|min:0',
        ]);

        $sws = (float) ($validated['sws_rate'] ?? 10.0);
        $igst = (float) ($validated['igst_rate'] ?? 18.0);
        $aidc = (float) ($validated['aidc_rate'] ?? 0.0);

        $result = $this->engine->compare(
            unitFobUnit: (float) $validated['unit_fob'],
            quantity: (float) $validated['quantity'],
            currency: $validated['currency'] ?? 'USD',
            oldRates: ['bcd' => (float) $validated['old_bcd'], 'sws' => $sws, 'igst' => $igst, 'aidc' => $aidc],
            newRates: ['bcd' => (float) $validated['new_bcd'], 'sws' => $sws, 'igst' => $igst, 'aidc' => $aidc],
            freightTotal: isset($validated['freight_total']) ? (float) $validated['freight_total'] : null,
            insuranceTotal: isset($validated['insurance_total']) ? (float) $validated['insurance_total'] : null,
            sellingPricePerUnit: isset($validated['target_selling_price']) ? (float) $validated['target_selling_price'] : null,
            customExchangeRate: isset($validated['custom_exchange_rate']) ? (float) $validated['custom_exchange_rate'] : null
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function simulateByHsCode(Request $request, string $code): JsonResponse
    {
        $cleanCode = preg_replace('/\s+/', '', $code);
        $hsCode = HsCode::where('code', $cleanCode)->with('latestRevision')->first();

        if (!$hsCode) {
            return response()->json([
                'success' => false,
                'message' => "HS Code {$cleanCode} not found in master database.",
            ], 404);
        }

        $latestRevision = $hsCode->latestRevision;
        $oldBcd = $latestRevision ? (float) $latestRevision->old_rate : (float) $hsCode->base_bcd_rate;
        $newBcd = $latestRevision ? (float) $latestRevision->new_rate : (float) $hsCode->base_bcd_rate;

        $unitFob = (float) ($request->input('unit_fob', 100.00));
        $quantity = (float) ($request->input('quantity', 1000));
        $currency = strtoupper((string) ($request->input('currency', 'USD')));

        $result = $this->engine->compare(
            unitFobUnit: $unitFob,
            quantity: $quantity,
            currency: $currency,
            oldRates: [
                'bcd' => $oldBcd,
                'sws' => (float) $hsCode->sws_rate,
                'igst' => (float) $hsCode->igst_rate,
                'aidc' => (float) $hsCode->aidc_rate,
            ],
            newRates: [
                'bcd' => $newBcd,
                'sws' => (float) $hsCode->sws_rate,
                'igst' => (float) $hsCode->igst_rate,
                'aidc' => (float) $hsCode->aidc_rate,
            ],
            sellingPricePerUnit: $request->has('target_selling_price') ? (float) $request->input('target_selling_price') : null
        );

        return response()->json([
            'success' => true,
            'hs_code' => $hsCode,
            'revision' => $latestRevision,
            'simulation' => $result,
        ]);
    }
}
