<?php

namespace App\Console\Commands;

use App\Services\MqttService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MqttAutoPublisher extends Command
{
    protected $signature = 'mqtt:auto-publish';
    protected $description = 'Publie automatiquement la position d’un chauffeur toutes les 20 secondes';

    private bool $running = true;

    public function handle(): int
    {
        Log::info("🚗 Simulation d’envoi automatique des positions toutes les 20 s...");

        $mqtt = new MqttService();

        $this->info("✅ Connecté au broker MQTT : " . config('mqtt.host') . ":" . config('mqtt.port'));

        $driverId = 'SIM001';
        $baseLat = 5.345;
        $baseLng = -4.012;

        while ($this->running) {
            $latitude = $baseLat + (mt_rand(-100, 100) / 10000);
            $longitude = $baseLng + (mt_rand(-100, 100) / 10000);
            $speed = rand(20, 100);
            $bearing = rand(0, 359);

            $message = json_encode([
                'driver_id' => $driverId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'speed' => $speed,
                'bearing' => $bearing,
            ]);

            $mqtt->publish("izycab/drivers/{$driverId}/location", $message);

            Log::info("📡 Position envoyée : $message");

            sleep(20);
        }

        return self::SUCCESS;

    }

    public function getSubscribedSignals(): array
    {
        return array_filter([
            defined('SIGINT') ? \SIGINT : null,
            defined('SIGTERM') ? \SIGTERM : null,
        ]);
    }

    public function handleSignal(int $signal): void
    {
        $this->running = false;
        Log::info('Arrêt demandé, dernière position envoyée.');
    }
}
