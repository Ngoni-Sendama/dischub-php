<?php

namespace Ngoni\DischubPhp\Tests\Redirect;

use InvalidArgumentException;
use Ngoni\DischubPhp\Redirect\RedirectIntegration;
use Ngoni\DischubPhp\Support\DischubConfig;
use Ngoni\DischubPhp\Support\PaymentValidator;
use Ngoni\DischubPhp\Tests\Fakes\FakeDischubClient;
use Ngoni\DischubPhp\Tests\TestCase;

final class RedirectIntegrationTest extends TestCase
{
    private DischubConfig $config;

    private FakeDischubClient $client;

    private RedirectIntegration $integration;

    protected function setUp(): void
    {
        $this->config = new DischubConfig([
            'api_key' => 'secret',
            'recipient' => 'merchant@example.com',
            'callback_url' => 'https://example.com/callback',
            'redirect_url' => 'https://example.com/complete',
            'mode' => 'test',
        ]);

        $this->client = new FakeDischubClient($this->config);
        $this->integration = new RedirectIntegration(
            $this->client,
            new PaymentValidator(),
            $this->config
        );
    }

    public function test_it_creates_an_order_with_static_config_values(): void
    {
        $response = $this->integration->createOrder([
            'order_id' => 344245663,
            'sender' => '+263780070488',
            'amount' => '14.58',
            'currency' => 'usd',
        ]);

        $this->assertSame('success', $response['status']);
        $this->assertCount(1, $this->client->orders);

        $payload = $this->client->orders[0];

        $this->assertSame('344245663', $payload['order_id']);
        $this->assertSame('+263780070488', $payload['sender']);
        $this->assertSame('merchant@example.com', $payload['recipient']);
        $this->assertEquals(14.58, $payload['amount']);
        $this->assertSame('USD', $payload['currency']);
        $this->assertSame('test', $payload['mode']);
        $this->assertSame('https://example.com/callback', $payload['callback_url']);
        $this->assertSame('https://example.com/complete', $payload['redirect_url']);
    }

    public function test_it_omits_optional_urls_when_not_configured(): void
    {
        $config = new DischubConfig([
            'api_key' => 'secret',
            'recipient' => 'merchant@example.com',
            'mode' => 'test',
        ]);

        $client = new FakeDischubClient($config);
        $integration = new RedirectIntegration($client, new PaymentValidator(), $config);

        $integration->createOrder([
            'order_id' => 'order-1',
            'sender' => '0780070488',
            'amount' => 1,
            'currency' => 'USD',
        ]);

        $this->assertArrayNotHasKey('callback_url', $client->orders[0]);
        $this->assertArrayNotHasKey('redirect_url', $client->orders[0]);
    }

    public function test_it_allows_per_order_overrides(): void
    {
        $this->integration->createOrder([
            'order_id' => 'order-1',
            'sender' => '0780070488',
            'amount' => 1,
            'currency' => 'USD',
            'mode' => 'live',
            'callback_url' => 'https://example.com/other-callback',
            'redirect_url' => 'https://example.com/other-complete',
        ]);

        $payload = $this->client->orders[0];

        $this->assertSame('live', $payload['mode']);
        $this->assertSame('https://example.com/other-callback', $payload['callback_url']);
        $this->assertSame('https://example.com/other-complete', $payload['redirect_url']);
    }

    public function test_it_builds_the_correct_payment_url(): void
    {
        $this->assertSame(
            'https://dischub.co.zw/api/make/payment/to/344245663',
            $this->integration->getPaymentUrl('344245663')
        );
    }

    public function test_it_gets_the_redirect_payment_status(): void
    {
        $this->integration->getPaymentStatus('order-1');

        $this->assertSame([
            'order_id' => 'order-1',
            'recipient' => 'merchant@example.com',
        ], $this->client->statuses[0]);
    }

    public function test_it_requires_the_core_order_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->integration->createOrder([
            'order_id' => 'order-1',
            'sender' => '0780070488',
            'currency' => 'USD',
        ]);
    }
}
