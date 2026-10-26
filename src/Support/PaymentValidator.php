<?php

namespace Ngoni\DischubPhp\Support;

use InvalidArgumentException;

class PaymentValidator
{
    public const CURRENCIES = ['USD', 'ZWG'];

    public const MODES = ['test', 'live'];

    public const PAYMENT_METHODS = ['ecocash', 'innbucks', 'omari', 'zimswitch'];

    /**
     * Accepted sender formats: +263780070488, 0263780070488 and 0780070488.
     */
    public const SENDER_PATTERN = '/^(?:\+263|0263|0)7\d{8}$/';

    /**
     * Accepted EcoCash number formats: +263780000000 and 0780000000.
     */
    public const ECOCASH_NUMBER_PATTERN = '/^(?:\+263|0)7\d{8}$/';

    /**
     * Accepted Omari number format: 263780000000.
     */
    public const OMARI_NUMBER_PATTERN = '/^2637\d{8}$/';

    public function orderId($value): string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('order_id must be posted as a string.');
        }

        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('order_id is required.');
        }

        if (mb_strlen($value) > 30) {
            throw new InvalidArgumentException('order_id must be 30 characters or less.');
        }

        return $value;
    }

    public function sender($value): string
    {
        return $this->matchingPhoneNumber(
            $value,
            'sender',
            self::SENDER_PATTERN,
            '+263780070488, 0263780070488 or 0780070488'
        );
    }

    public function ecocashNumber($value): string
    {
        return $this->matchingPhoneNumber(
            $value,
            'ecocash_number',
            self::ECOCASH_NUMBER_PATTERN,
            '+263780000000 or 0780000000'
        );
    }

    public function omariNumber($value): string
    {
        return $this->matchingPhoneNumber(
            $value,
            'omari_number',
            self::OMARI_NUMBER_PATTERN,
            '263780000000'
        );
    }

    public function amount($value)
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('amount must be a valid number.');
        }

        $amount = (float) $value;

        if (! is_finite($amount) || $amount <= 0 || $amount >= 100000) {
            throw new InvalidArgumentException('amount must be greater than 0 and less than 100000.');
        }

        return $amount;
    }

    public function currency($value): string
    {
        $value = strtoupper($this->stringValue($value, 'currency'));

        if (! in_array($value, self::CURRENCIES, true)) {
            throw new InvalidArgumentException('currency must be one of: USD, ZWG.');
        }

        return $value;
    }

    public function mode($value): string
    {
        $value = strtolower($this->stringValue($value, 'mode'));

        if (! in_array($value, self::MODES, true)) {
            throw new InvalidArgumentException('mode must be either test or live.');
        }

        return $value;
    }

    public function paymentMethod($value): string
    {
        $value = strtolower($this->stringValue($value, 'payment_method'));

        if (! in_array($value, self::PAYMENT_METHODS, true)) {
            throw new InvalidArgumentException(
                'payment_method must be one of: ' . implode(', ', self::PAYMENT_METHODS) . '.'
            );
        }

        return $value;
    }

    public function callbackUrl($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return $this->url($value, 'callback_url', ['https']);
    }

    public function redirectUrl($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return $this->url($value, 'redirect_url', ['http', 'https']);
    }

    public function requiredString(array $data, string $key, ?string $field = null): string
    {
        if (! array_key_exists($key, $data)) {
            throw new InvalidArgumentException(($field ?? $key) . ' is required.');
        }

        return $this->stringValue($data[$key], $field ?? $key);
    }

    protected function url($value, string $field, array $schemes): string
    {
        $value = $this->stringValue($value, $field);

        $scheme = parse_url($value, PHP_URL_SCHEME);
        $host = parse_url($value, PHP_URL_HOST);

        if ($scheme === null || $host === null || ! in_array(strtolower($scheme), $schemes, true)) {
            throw new InvalidArgumentException(
                $field . ' must be a valid URL using the ' . implode(' or ', $schemes) . ' scheme.'
            );
        }

        return $value;
    }

    protected function matchingPhoneNumber($value, string $field, string $pattern, string $accepted): string
    {
        $value = $this->stringValue($value, $field);

        if (! preg_match($pattern, $value)) {
            throw new InvalidArgumentException(
                "invalid {$field} phone number format. Accepted formats: {$accepted}."
            );
        }

        return $value;
    }

    protected function stringValue($value, string $field): string
    {
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException($field . ' is required.');
        }

        return trim($value);
    }
}
