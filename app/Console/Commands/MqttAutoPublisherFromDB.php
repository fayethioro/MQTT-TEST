<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MqttService;
use App\Models\Driver;

class MqttAutoPublisherFromDB extends Command
{
    protected $signature = 'mqtt:auto-publish-db';
    protected $description = 'Publie automatiquement les positions des chauffeurs depuis la table drivers.';

    public function handle()
    {
        $this->info("🚗 Envoi automatique des positions des chauffeurs actifs...");

        $mqtt = new MqttService();

        while (true) {
            // 1️⃣ Récupération des chauffeurs actifs
            $drivers = Driver::where('active', true)->get();

            if ($drivers->isEmpty()) {
                $this->warn("⚠️ Aucun chauffeur actif trouvé dans la base.");
                sleep(20);
                continue;
            }

            // 2️⃣ Publication de la position pour chaque chauffeur
            foreach ($drivers as $driver) {
                $latitude = 14.745 + (mt_rand(-100, 100) / 10000);
                $longitude = -17.449 + (mt_rand(-100, 100) / 10000);
                $speed = rand(20, 100);
                $bearing = rand(0, 359);

                $message = json_encode([
                    'driver_id' => $driver->id,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'speed' => $speed,
                    'bearing' => $bearing,
                ]);

                $topic = "izycab/drivers/{$driver->id}/location";
                $mqtt->publish($topic, $message);

                $this->info("📡 [{$driver->name}] {$message}");
            }

            // 3️⃣ Pause entre deux envois
            sleep(20);
        }
    }
}
