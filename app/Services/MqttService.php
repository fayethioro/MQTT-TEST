<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttService
{
    protected $client;

    public function __construct()
    {
        $server = config('mqtt.host', '127.0.0.1');
        $port = config('mqtt.port', 1883);
        $username = config('mqtt.username');
        $password = config('mqtt.password');

        // 🔹 clientId unique pour éviter les déconnexions croisées
        $clientId = 'laravel_' . uniqid();
        Log::info("Client ID: $clientId");


        $settings = (new ConnectionSettings)
            ->setKeepAliveInterval(60)
            ->setReconnectAutomatically(true)
            ->setDelayBetweenReconnectAttempts(5)
            ->setMaxReconnectAttempts(999999)
            ->setUseTls(false)
            ->setLastWillTopic('izycab/lastwill')
            ->setLastWillMessage('Laravel MQTT déconnecté')
            ->setLastWillQualityOfService(1);

        if (!empty($username)) {
            $settings->setUsername($username);
        }
        if (!empty($password)) {
            $settings->setPassword($password);
        }

        $this->client = new MqttClient($server, $port, $clientId);

        try {
            $this->client->connect($settings, false);
            Log::info("✅ Connecté au broker MQTT : $server:$port (ID: $clientId)");
        } catch (\Throwable $e) {
            Log::error('❌ Erreur connexion MQTT', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function publish($topic, $message)
    {
        $this->client->publish($topic, $message, 1);
    }

    public function subscribe($topic, callable $callback)
    {
        $this->client->subscribe($topic, $callback, 1);
        $this->client->loop(true);
    }

    public function disconnect()
    {
        $this->client->disconnect();
    }
}
