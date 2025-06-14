<?php

namespace App\Services\Crypto;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class BinanceService
{
    protected $client;
    protected $apiKey;
    protected $apiSecret;
    protected $baseUrl = 'https://api.binance.com';

    public function __construct()
    {
        $this->apiKey = config('services.binance.api_key');
        $this->apiSecret = config('services.binance.api_secret');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'X-MBX-APIKEY' => $this->apiKey,
            ],
        ]);
    }

    public function getAccountInfo()
    {
        try {
            $timestamp = time() * 1000;
            $queryString = "timestamp={$timestamp}";
            $signature = hash_hmac('sha256', $queryString, $this->apiSecret);

            $response = $this->client->get('/api/v3/account', [
                'query' => [
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Binance API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getMarketData($symbol)
    {
        try {
            $response = $this->client->get('/api/v3/ticker/24hr', [
                'query' => ['symbol' => $symbol],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Binance API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getKlines($symbol, $interval = '1h', $limit = 100)
    {
        try {
            $response = $this->client->get('/api/v3/klines', [
                'query' => [
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => $limit,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Binance API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createOrder($symbol, $side, $type, $quantity, $price = null)
    {
        try {
            $timestamp = time() * 1000;
            $params = [
                'symbol' => $symbol,
                'side' => $side,
                'type' => $type,
                'quantity' => $quantity,
                'timestamp' => $timestamp,
            ];

            if ($price) {
                $params['price'] = $price;
            }

            $queryString = http_build_query($params);
            $signature = hash_hmac('sha256', $queryString, $this->apiSecret);
            $params['signature'] = $signature;

            $response = $this->client->post('/api/v3/order', [
                'form_params' => $params,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Binance API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getOrderStatus($symbol, $orderId)
    {
        try {
            $timestamp = time() * 1000;
            $queryString = "symbol={$symbol}&orderId={$orderId}&timestamp={$timestamp}";
            $signature = hash_hmac('sha256', $queryString, $this->apiSecret);

            $response = $this->client->get('/api/v3/order', [
                'query' => [
                    'symbol' => $symbol,
                    'orderId' => $orderId,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Binance API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function cancelOrder($symbol, $orderId)
    {
        try {
            $timestamp = time() * 1000;
            $queryString = "symbol={$symbol}&orderId={$orderId}&timestamp={$timestamp}";
            $signature = hash_hmac('sha256', $queryString, $this->apiSecret);

            $response = $this->client->delete('/api/v3/order', [
                'query' => [
                    'symbol' => $symbol,
                    'orderId' => $orderId,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Binance API Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
