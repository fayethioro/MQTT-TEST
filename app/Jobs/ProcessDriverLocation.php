<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Services\MqttService;
use Illuminate\Bus\Queueable;
use App\Models\DriverLocation;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use App\Models\DriverLocationHistorique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessDriverLocation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $driverId;
    public array $payload;

    public function __construct(string $driverId, array $payload)
    {
        $this->driverId = $driverId;
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $lat = (float) $this->payload['latitude'];
        $lng = (float) $this->payload['longitude'];
        $accuracy = $this->payload['accuracy'] ?? null;
        $speed = $this->payload['speed'] ?? null;
        $bearing = $this->payload['bearing'] ?? null;
        $provider = $this->payload['provider'] ?? 'gps';
        $ts = isset($this->payload['timestamp']) ? Carbon::parse($this->payload['timestamp']) : now();

        Log::info("JOB: 📡 [{$this->driverId}] Position mise à jour", ['latitude' => $lat, 'longitude' => $lng, 'accuracy' => $accuracy, 'speed' => $speed, 'bearing' => $bearing, 'provider' => $provider, 'timestamp' => $ts]);
        // 1) Met à jour la position courante
        DriverLocation::updateOrCreate(
            ['driver_id' => $this->driverId],
            [
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => $accuracy,
                'speed' => $speed,
                'bearing' => $bearing,
                'provider' => $provider,
                'last_seen' => $ts,
            ]
        );

        // 2) Insère un point d'historique selon règle (temps ou distance)
        $shouldInsertHistory = true;
        $last = DriverLocationHistorique::where('driver_id', $this->driverId)->latest('captured_at')->first();
        if ($last) {
            if ($last->captured_at && $last->captured_at->diffInSeconds($ts) < 20) {
                // Option A: toutes les 60s minimum
                $shouldInsertHistory = false;
            }
            // Option B (distance) : si tu veux,
            // calcule la distance entre ($last->lat,$last->lng) et ($lat,$lng) et n'insère que si > 5 m.
        }

        if ($shouldInsertHistory) {
            DriverLocationHistorique::create([
                'driver_id' => $this->driverId,
                'latitude' => $lat,
                'longitude' => $lng,
                'speed' => $speed,
                'bearing' => $bearing,
                'accuracy' => $accuracy,
                'provider' => $provider,
                'captured_at' => now(),
            ]);

        }

        // 3) Publication live pour le front (Leaflet + MQTT.js)
        try {
            $mqtt = new MqttService();
            $mqtt->publish("izycab/drivers/{$this->driverId}/location", json_encode([
                'driver_id' => $this->driverId,
                'latitude'  => $lat,
                'longitude' => $lng,
                'accuracy'  => $accuracy,
                'speed'     => $speed,
                'bearing'   => $bearing,
                'timestamp' => $ts->toIso8601String(),
            ]));
            Log::info("MQTT publish success", ['driver_id' => $this->driverId, 'data' => json_encode([
                'driver_id' => $this->driverId,
                'latitude'  => $lat,
                'longitude' => $lng,
                'accuracy'  => $accuracy,
                'speed'     => $speed,
                'bearing'   => $bearing,
                'timestamp' => $ts->toIso8601String(),
            ])] );
        } catch (\Throwable $e) {
            Log::warning('MQTT publish failed', ['error' => $e->getMessage()]);
            Log::warning('MQTT publish failed', ['error' => $e->getMessage()]);
        }
    }
}
