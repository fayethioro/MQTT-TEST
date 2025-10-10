<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MqttService;

class MqttAutoPublisherMulti extends Command
{
    protected $signature = 'mqtt:auto-publish-multi {--cycles=1 : Nombre de cycles de publication}';
    protected $description = 'Simule plusieurs chauffeurs publiant leurs positions toutes les 20 secondes';

    public function  handle()
    {
        $this->info("🚗 Simulation multi-chauffeurs MQTT...");

        $mqtt = new MqttService();

        $drivers = ['CH001', 'CH002', 'CH003']; // 👉 liste des chauffeurs simulés

        while (true) {
            foreach ($drivers as $driverId) {
                $latitude = 5.345 + (mt_rand(-100, 100) / 10000);
                $longitude = -4.012 + (mt_rand(-100, 100) / 10000);
                $speed = rand(20, 100);
                $bearing = rand(0, 359);

                $message = json_encode([
                    'driver_id' => $driverId,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'speed' => $speed,
                    'bearing' => $bearing,
                ]);

                $topic = "izycab/drivers/{$driverId}/location";
                $mqtt->publish($topic, $message);

                $this->info("📡 [$driverId] $message");
            }

            sleep(20);
        }
    }
}
