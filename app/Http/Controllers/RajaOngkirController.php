<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RajaOngkirController extends Controller
{
    private function api()
    {
        return Http::timeout(20)
            ->withHeaders([
                'key' => config('services.rajaongkir.key'),
            ]);
    }

    public function getProvinces()
    {
        $response = $this->api()->get(
            'https://rajaongkir.komerce.id/api/v1/destination/province'
        );

        if ($response->failed()) {
            return response()->json([
                'message' => 'Gagal mengambil data provinsi',
                'detail' => $response->json(),
            ], $response->status());
        }

        return response()->json($response->json());
    }

    
    
public function getCities(Request $request)
{
    $request->validate([
        'province_id' => 'required|integer',
    ]);

    $provinceId = $request->province_id;

    $response = $this->api()->get(
        'https://rajaongkir.komerce.id/api/v1/destination/city/' . $provinceId
    );

    if ($response->failed()) {
        return response()->json([
            'message' => 'Gagal mengambil kota',
            'http_status' => $response->status(),
            'detail' => $response->json() ?? $response->body(),
        ], $response->status());
    }

    return response()->json($response->json());
}



    public function getCost(Request $request)
    {
        $validated = $request->validate([
            'origin' => 'required|integer',
            'destination' => 'required|integer',
            'weight' => 'required|integer|min:1',
            'courier' => 'required|string',
        ]);

        $response = $this->api()->asForm()->post(
            'https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost',
            [
                'origin' => $validated['origin'],
                'destination' => $validated['destination'],
                'weight' => $validated['weight'],
                'courier' => $validated['courier'],
            ]
        );

        if ($response->failed()) {
            return response()->json([
                'message' => 'Gagal menghitung ongkos kirim',
                'detail' => $response->json(),
            ], $response->status());
        }

        return response()->json($response->json());
    }
}
