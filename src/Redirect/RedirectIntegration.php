<?php

namespace Ngoni\DischubPhp\Redirect;

use Ngoni\DischubPhp\Support\DischubClient;
use Ngoni\DischubPhp\Support\DischubConfig;
use Ngoni\DischubPhp\Support\PaymentValidator;

class RedirectIntegration
{
    protected DischubClient $client;

    protected PaymentValidator $validator;

    protected DischubConfig $config;

    public function __construct(
        DischubClient $client,
        PaymentValidator $validator,
        DischubConfig $config
    ) {
        $this->client = $client;
        $this->validator = $validator;
        $this->config = $config;
    }

    /**
     * Create an order for the redirect integration.
     *
     * Required in $data: order_id, sender, amount, currency.
     * Optional overrides: callback_url, redirect_url, mode.
     */
    public function createOrder(array $data): array
    {
        $this->requireKeys($data, ['order_id', 'sender', 'amount', 'currency']);

        $callbackUrl = $this->validator->callbackUrl(
            $data['callback_url'] ?? $this->config->callbackUrl()
        );

        $redirectUrl = $this->validator->redirectUrl(
            $data['redirect_url'] ?? $this->config->redirectUrl()
        );

        $payload = [
            'order_id' => $this->validator->orderId($data['order_id']),
            'sender' => $this->validator->sender($data['sender']),
            'recipient' => $this->config->recipient(),
            'amount' => $this->validator->amount($data['amount']),
            'currency' => $this->validator->currency($data['currency']),
            'mode' => $this->validator->mode($data['mode'] ?? $this->config->mode()),
        ];

        if ($callbackUrl !== null) {
            $payload['callback_url'] = $callbackUrl;
        }

        if ($redirectUrl !== null) {
            $payload['redirect_url'] = $redirectUrl;
        }

        return $this->client->createOrder($payload);
    }

    /**
     * Get the URL the customer is redirected to in order to pay.
     */
    public function getPaymentUrl($orderId): string
    {
        $orderId = $this->validator->orderId($orderId);

        return rtrim($this->config->paymentUrl(), '/') . '/' . rawurlencode($orderId);
    }

    /**
     * Get the status of a redirect order.
     */
    public function getPaymentStatus($orderId): array
    {
        return $this->client->getPaymentStatus([
            'order_id' => $this->validator->orderId($orderId),
            'recipient' => $this->config->recipient(),
        ]);
    }

    protected function requireKeys(array $data, array $keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                throw new \InvalidArgumentException($key . ' is required.');
            }
        }
    }
}
