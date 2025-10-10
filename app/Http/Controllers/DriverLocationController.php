<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\MqttService;
use App\Models\DriverLocation;
use App\Jobs\ProcessDriverLocation;
use Illuminate\Support\Facades\Log;

class DriverLocationController extends Controller
{

    public function update(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|uuid|exists:drivers,id',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed'     => 'nullable|numeric',
            'bearing'   => 'nullable|integer|min:0|max:359',
            'accuracy'  => 'nullable|numeric|min:0',
            'provider'  => 'nullable|string|max:50',
        ]);

        try {
            // Met à jour la localisation du chauffeur
            $location = DriverLocation::updateOrCreate(
                ['driver_id' => $validated['driver_id']],
                [
                    'latitude'   => $validated['latitude'],
                    'longitude'  => $validated['longitude'],
                    'speed'      => $validated['speed'] ?? null,
                    'bearing'    => $validated['bearing'] ?? null,
                    'accuracy'   => $validated['accuracy'] ?? null,
                    'provider'   => $validated['provider'] ?? 'gps',
                    'last_seen'  => Carbon::now(),
                ]
            );

            // ✅ Publier sur MQTT pour mise à jour temps réel
            try {
                $mqtt = new MqttService();
                $mqtt->publish(
                    "izycab/drivers/{$validated['driver_id']}/location",
                    json_encode([
                        'driver_id' => $validated['driver_id'],
                        'latitude'  => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                        'speed'     => $validated['speed'] ?? null,
                        'bearing'   => $validated['bearing'] ?? null,
                        'accuracy'  => $validated['accuracy'] ?? null,
                        'provider'  => $validated['provider'] ?? 'gps',
                        'timestamp' => now()->toIso8601String(),
                    ])
                );
                // $this->info("📡 [{$validated['driver_id']}] Position mise à jour");
                Log::info("📡 [{$validated['driver_id']}] Position mise à jour");

            } catch (\Throwable $e) {
                Log::warning('MQTT publish failed', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'status' => 'ok',
                'message' => 'Position mise à jour avec succès.',
                'data' => $location,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Erreur update localisation', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Erreur interne.'], 500);
        }
    }



    public function updateMulti(Request $request)
    {
        // $user = $request->user(); // si tes chauffeurs s'authentifient via Sanctum

        $validated = $request->validate([
            'driver_id' => 'required|uuid|exists:drivers,id',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy'  => 'nullable|numeric|min:0',
            'speed'     => 'nullable|numeric',
            'bearing'   => 'nullable|integer|min:0|max:359',
            'timestamp' => 'nullable|date',
            'provider'  => 'nullable|string|max:50',
        ]);

        $driverId = $validated['driver_id'];    // adapte si tu as une table drivers séparée

        ProcessDriverLocation::dispatch($driverId, $validated);

        return response()->json(['status' => 'accepted'], 202);
    }


}
