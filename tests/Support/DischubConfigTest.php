<?php

namespace Ngoni\DischubPhp\Tests\Support;

use InvalidArgumentException;
use Ngoni\DischubPhp\Support\DischubConfig;
use Ngoni\DischubPhp\Tests\TestCase;

final class DischubConfigTest extends TestCase
{
    public function test_it_reads_static_values(): void
    {
        $config = new DischubConfig([
            'api_key' => 'secret',
            'recipient' => 'merchant@example.com',
            'callback_url' => 'https://example.com/callback',
            'redirect_url' => 'https://example.com/complete',
            'mode' => 'live',
        ]);

        $this->assertSame('secret', $config->apiKey());
        $this->assertSame('merchant@example.com', $config->recipient());
        $this->assertSame('https://example.com/callback', $config->callbackUrl());
        $this->assertSame('https://example.com/complete', $config->redirectUrl());
        $this->assertSame('live', $config->mode());
    }

    public function test_it_falls_back_to_legacy_config_keys(): void
    {
        $config = new DischubConfig([
            'api_key' => 'secret',
            'recipient' => 'merchant@example.com',
            'notify_url' => 'https://example.com/legacy-callback',
        ]);

        $this->assertSame('https://example.com/legacy-callback', $config->callbackUrl());
    }

    public function test_it_uses_default_endpoints_and_mode(): void
    {
        $config = new DischubConfig([
            'api_key' => 'secret',
            'recipient' => 'merchant@example.com',
        ]);

        $this->assertSame('https://dischub.co.zw/api/orders/create/', $config->ordersUrl());
        $this->assertSame('https://dischub.co.zw/api/make/payment/to/', $config->paymentUrl());
        $this->assertSame('https://dischub.co.zw/api/payment/status/3/step/', $config->statusUrl());
        $this->assertSame(
            'https://dischub.co.zw/api/zimswitch/non/redirect/payment/to/',
            $config->zimswitchWidgetUrl()
        );
        $this->assertSame('test', $config->mode());
    }

    public function test_it_supports_the_legacy_api_url_key(): void
    {
        $config = new DischubConfig([
            'api_key' => 'secret',
            'recipient' => 'merchant@example.com',
            'api_url' => 'https://example.com/orders',
        ]);

        $this->assertSame('https://example.com/orders', $config->ordersUrl());
    }

    public function test_it_requires_an_api_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DISCHUB_API_KEY');

        (new DischubConfig([]))->apiKey();
    }

    public function test_it_requires_a_recipient(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DISCHUB_RECIPIENT');

        (new DischubConfig([]))->recipient();
    }
}
