<?php

namespace App\Http\Controllers;

use App\Services\BiteshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicShippingController extends Controller
{
    /**
     * Search areas (cities/districts) for autocomplete.
     */
    public function searchAreas(Request $request, BiteshipService $biteship): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $areas = $biteship->searchAreas($validated['q']);

        return response()->json([
            'success' => true,
            'areas' => $areas,
        ]);
    }

    /**
     * Calculate public shipping rates between origin and destination.
     */
    public function calculateRates(Request $request, BiteshipService $biteship): JsonResponse
    {
        $validated = $request->validate([
            'origin_area_id' => ['required', 'string', 'max:100'],
            'destination_area_id' => ['required', 'string', 'max:100'],
            'weight' => ['required', 'numeric', 'min:0.1', 'max:10'],
        ], [
            'weight.max' => 'Berat paket maksimal 10 kg untuk layanan parsel retail reguler/express.',
            'weight.min' => 'Berat paket minimal 0.1 kg (100 gram).',
        ]);

        $weightInGrams = (int) round($validated['weight'] * 1000);

        $result = $biteship->checkRates(
            $validated['origin_area_id'],
            $validated['destination_area_id'],
            $weightInGrams
        );

        return response()->json($result);
    }

    /**
     * Public waybill live tracking.
     */
    public function trackWaybill(Request $request, BiteshipService $biteship): JsonResponse
    {
        $validated = $request->validate([
            'waybill' => ['required', 'string', 'max:80'],
            'courier' => ['required', 'string', 'max:30'],
        ]);

        $result = $biteship->trackWaybill(
            $validated['waybill'],
            $validated['courier']
        );

        return response()->json($result);
    }
}
