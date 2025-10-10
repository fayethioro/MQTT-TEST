<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MqttService;
use App\Models\DriverLocation;
use Illuminate\Support\Facades\Log;
use Exception;

class MqttListen extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Écoute les positions envoyées via MQTT et les enregistre en base';

    public function handle()
    {
        $this->info("🚀 Démarrage du listener MQTT...");

        try {
            $mqtt = new MqttService();

            $this->info("✅ Connecté au broker MQTT : " . config('mqtt.host') . ":" . config('mqtt.port') );
            $this->info("🛰️  En attente de positions sur le topic : izycab/drivers/+/location\n");

            $mqtt->subscribe('izycab/drivers/+/location', function (string $topic, string $message) {
                try {
                    $data = json_decode($message, true);

                    if (!$data || !isset($data['driver_id'])) {
                        echo "⚠️  Message ignoré (format invalide) : $message\n";
                        Log::warning('MQTT message ignoré', ['message' => $message]);
                        return;
                    }

                    $location = DriverLocation::updateOrCreate(
                        ['driver_id' => $data['driver_id']],
                        [
                            'latitude' => $data['latitude'] ?? null,
                            'longitude' => $data['longitude'] ?? null,
                            'speed' => $data['speed'] ?? null,
                            'bearing' => $data['bearing'] ?? null,
                        ]
                    );

                    echo "✅ Chauffeur {$data['driver_id']} : ({$data['latitude']}, {$data['longitude']})\n";
                    Log::info('Position mise à jour', ['driver_id' => $data['driver_id'], 'data' => $location->toArray()]);
                } catch (Exception $e) {
                    Log::error('Erreur traitement MQTT', ['error' => $e->getMessage()]);
                    echo "❌ Erreur : {$e->getMessage()}\n";
                }
            });
        } catch (Exception $e) {
            Log::error('Erreur connexion MQTT', ['error' => $e->getMessage()]);
            $this->error("❌ Impossible de se connecter au broker : {$e->getMessage()}");
        }
    }
}
