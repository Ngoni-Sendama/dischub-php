<?php

namespace Ngoni\DischubPhp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DischubService
{
    protected string $apiUrl;
    protected string $paymentUrl;
    protected string $notifyUrl;
    protected string $apiKey;
    protected string $recipient;

    public function __construct()
    {
        $this->apiUrl = config('dischub.api_url', 'https://dischub.co.zw/api/orders/create/');
        $this->paymentUrl = config('dischub.payment_url', 'https://dischub.co.zw/api/make/payment/to/');
        $this->apiKey = config('dischub.api_key');
        $this->notifyUrl = config('dischub.notify_url');
        $this->recipient = config('dischub.recipient');
    }

    /**
     * Create an order via Dischub API.
     */
    public function createOrder(array $data)
    {
        $response = Http::post($this->apiUrl, [
            'api_key'    => $this->apiKey,
            'notify_url' => $this->notifyUrl,
            'order_id'   => $data['order_id'],
            'sender'     => $data['sender'],
            'recipient'  => $this->recipient,
            'amount'     => $data['amount'],
            'currency'   => $data['currency'],
        ]);

        return $response->json();
    }

    /**
     * Get the payment URL for an order.
     */
    public function getPaymentUrl(int $orderId): string
    {
        return "{$this->paymentUrl}{$this->recipient}/{$orderId}";
    }
}
