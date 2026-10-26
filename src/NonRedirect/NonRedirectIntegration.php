<?php

namespace Ngoni\DischubPhp\NonRedirect;

use InvalidArgumentException;
use Ngoni\DischubPhp\Support\DischubClient;
use Ngoni\DischubPhp\Support\DischubConfig;
use Ngoni\DischubPhp\Support\PaymentValidator;

class NonRedirectIntegration
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
     * Create a non-redirect order using one of the supported payment methods.
     *
     * Required in $data: order_id, sender, amount, currency.
     * EcoCash also requires ecocash_number.
     * Omari also requires omari_number.
     */
    public function createOrder(string $paymentMethod, array $data): array
    {
        $method = $this->validator->paymentMethod($paymentMethod);

        $this->requireKeys($data, ['order_id', 'sender', 'amount', 'currency']);

        $payload = [
            'order_id' => $this->validator->orderId($data['order_id']),
            'sender' => $this->validator->sender($data['sender']),
            'recipient' => $this->config->recipient(),
            'amount' => $this->validator->amount($data['amount']),
            'currency' => $this->validator->currency($data['currency']),
            'payment_method' => $method,
        ];

        if ($method === 'ecocash') {
            $payload['payment_details'] = [
                'ecocash_number' => $this->validator->ecocashNumber($data['ecocash_number'] ?? null),
            ];
        }

        if ($method === 'omari') {
            $payload['payment_details'] = [
                'omari_number' => $this->validator->omariNumber($data['omari_number'] ?? null),
            ];
        }

        if ($method === 'zimswitch') {
            $callbackUrl = $this->validator->callbackUrl(
                $data['callback_url'] ?? $this->config->callbackUrl()
            );

            if ($callbackUrl !== null) {
                $payload['callback_url'] = $callbackUrl;
            }
        }

        return $this->client->createOrder($payload);
    }

    public function ecocash(array $data): array
    {
        return $this->createOrder('ecocash', $data);
    }

    public function innbucks(array $data): array
    {
        return $this->createOrder('innbucks', $data);
    }

    public function omari(array $data): array
    {
        return $this->createOrder('omari', $data);
    }

    public function zimswitch(array $data): array
    {
        return $this->createOrder('zimswitch', $data);
    }

    /**
     * Validate the OTP that was sent to the customer for an Omari payment.
     */
    public function validateOmariOtp($orderId, $otp): array
    {
        return $this->getPaymentStatus($orderId, 'omari', ['omari_otp' => $otp]);
    }

    /**
     * Get the status of a non-redirect order.
     *
     * Omari requires the OTP in $paymentDetails as ["omari_otp" => "..."].
     */
    public function getPaymentStatus($orderId, ?string $paymentMethod = null, array $paymentDetails = []): array
    {
        $payload = [
            'order_id' => $this->validator->orderId($orderId),
            'recipient' => $this->config->recipient(),
        ];

        if ($paymentMethod !== null) {
            $method = $this->validator->paymentMethod($paymentMethod);

            if ($method === 'omari') {
                $payload['payment_method'] = 'omari';
                $payload['payment_details'] = [
                    'omari_otp' => $this->validator->requiredString($paymentDetails, 'omari_otp'),
                ];
            }
        }

        return $this->client->getPaymentStatus($payload);
    }

    /**
     * Get the URL for the embedded ZimSwitch payment widget.
     */
    public function getZimswitchWidgetUrl($orderId): string
    {
        $orderId = $this->validator->orderId($orderId);

        return rtrim($this->config->zimswitchWidgetUrl(), '/') . '/' . rawurlencode($orderId);
    }

    public static function paymentMethods(): array
    {
        return PaymentValidator::PAYMENT_METHODS;
    }

    protected function requireKeys(array $data, array $keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                throw new InvalidArgumentException($key . ' is required.');
            }
        }
    }
}
