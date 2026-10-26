<?php

namespace Ngoni\DischubPhp\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DischubClient
{
    protected DischubConfig $config;

    public function __construct(DischubConfig $config)
    {
        $this->config = $config;
    }

    public function createOrder(array $payload): array
    {
        return $this->post($this->config->ordersUrl(), $payload);
    }

    public function getPaymentStatus(array $payload): array
    {
        return $this->post($this->config->statusUrl(), $payload);
    }

    public function post(string $url, array $payload): array
    {
        try {
            $response = Http::timeout($this->config->timeout())
                ->connectTimeout($this->config->connectTimeout())
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'ngoni/dischub-php',
                    'X-Api-Key' => $this->config->apiKey(),
                ])
                ->post($url, $payload);
        } catch (ConnectionException $exception) {
            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
                'response_code' => 0,
            ];
        }

        $data = $response->json();

        if (! is_array($data)) {
            return [
                'status' => $response->successful() ? 'success' : 'error',
                'message' => $response->body(),
                'response_code' => $response->status(),
            ];
        }

        return $data;
    }
}
