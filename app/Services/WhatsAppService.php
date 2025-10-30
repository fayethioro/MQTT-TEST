<?php

namespace App\Services;

use GuzzleHttp\Client;

class WhatsAppService
{
    protected $client;
    protected $token;
    protected $phoneId;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://graph.facebook.com/v22.0/803718556166713/messages']);
        $this->token = env('WHATSAPP_TOKEN');
        $this->phoneId = env('WHATSAPP_PHONE_ID');
    }

    /**
     * Envoie un message WhatsApp
     */
    public function sendMessage($to, $message)
    {
        $response = $this->client->post("{$this->phoneId}/messages", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}
