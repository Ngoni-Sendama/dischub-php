<?php

namespace Ngoni\DischubPhp\Support;

use InvalidArgumentException;

class DischubConfig
{
    protected array $values;

    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function apiKey(): string
    {
        return $this->required('api_key', 'DISCHUB_API_KEY');
    }

    public function recipient(): string
    {
        return $this->required('recipient', 'DISCHUB_RECIPIENT');
    }

    public function callbackUrl(): ?string
    {
        return $this->nullableString($this->values['callback_url'] ?? null)
            ?? $this->nullableString($this->values['notify_url'] ?? null);
    }

    public function redirectUrl(): ?string
    {
        return $this->nullableString($this->values['redirect_url'] ?? null);
    }

    public function mode(): string
    {
        return $this->nullableString($this->values['mode'] ?? null) ?? 'test';
    }

    public function ordersUrl(): string
    {
        return $this->url(
            $this->values['orders_url'] ?? $this->values['api_url'] ?? null,
            'https://dischub.co.zw/api/orders/create/'
        );
    }

    public function paymentUrl(): string
    {
        return $this->url(
            $this->values['payment_url'] ?? null,
            'https://dischub.co.zw/api/make/payment/to/'
        );
    }

    public function statusUrl(): string
    {
        return $this->url(
            $this->values['status_url'] ?? null,
            'https://dischub.co.zw/api/payment/status/3/step/'
        );
    }

    public function zimswitchWidgetUrl(): string
    {
        return $this->url(
            $this->values['zimswitch_widget_url'] ?? null,
            'https://dischub.co.zw/api/zimswitch/non/redirect/payment/to/'
        );
    }

    public function timeout(): float
    {
        return $this->positiveNumber($this->values['timeout'] ?? null, 30.0);
    }

    public function connectTimeout(): float
    {
        return $this->positiveNumber($this->values['connect_timeout'] ?? null, 10.0);
    }

    public function toArray(): array
    {
        return $this->values;
    }

    protected function required(string $key, string $env): string
    {
        $value = $this->nullableString($this->values[$key] ?? null);

        if ($value === null) {
            throw new InvalidArgumentException(
                "Dischub {$key} is not configured. Set {$env} in your .env file."
            );
        }

        return $value;
    }

    protected function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function url($value, string $default): string
    {
        return $this->nullableString($value) ?? $default;
    }

    protected function positiveNumber($value, float $default): float
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            return $default;
        }

        return (float) $value;
    }
}
