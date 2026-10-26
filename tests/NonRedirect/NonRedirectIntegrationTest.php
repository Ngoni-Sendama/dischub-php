<?php

namespace Ngoni\DischubPhp\Tests\NonRedirect;

use InvalidArgumentException;
use Ngoni\DischubPhp\NonRedirect\NonRedirectIntegration;
use Ngoni\DischubPhp\Support\DischubConfig;
use Ngoni\DischubPhp\Support\PaymentValidator;
use Ngoni\DischubPhp\Tests\Fakes\FakeDischubClient;
use Ngoni\DischubPhp\Tests\TestCase;

final class NonRedirectIntegrationTest extends TestCase
{
    private DischubConfig $config;

    private FakeDischubClient $client;

    private NonRedirectIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new DischubConfig([
            'api_key' => 'secret',
            'recipient' => 'merchant@example.com',
            'callback_url' => 'https://example.com/callback',
        ]);

        $this->client = new FakeDischubClient($this->config);
        $this->integration = new NonRedirectIntegration(
            $this->client,
            new PaymentValidator(),
            $this->config
        );
    }

    public function test_it_creates_an_ecocash_order(): void
    {
        $this->integration->ecocash([
            'order_id' => 'order-1',
            'sender' => '+263780070488',
            'amount' => 14.58,
            'currency' => 'usd',
            'ecocash_number' => '+263780000000',
        ]);

        $payload = $this->client->orders[0];

        $this->assertSame('order-1', $payload['order_id']);
        $this->assertSame('merchant@example.com', $payload['recipient']);
        $this->assertSame('USD', $payload['currency']);
        $this->assertSame('ecocash', $payload['payment_method']);
        $this->assertSame('+263780000000', $payload['payment_details']['ecocash_number']);
        $this->assertArrayNotHasKey('mode', $payload);
        $this->assertArrayNotHasKey('callback_url', $payload);
        $this->assertArrayNotHasKey('redirect_url', $payload);
    }

    public function test_it_creates_an_innbucks_order_without_payment_details(): void
    {
        $this->integration->innbucks([
            'order_id' => 'order-2',
            'sender' => '0780070488',
            'amount' => 10,
            'currency' => 'ZWG',
        ]);

        $payload = $this->client->orders[0];

        $this->assertSame('innbucks', $payload['payment_method']);
        $this->assertArrayNotHasKey('payment_details', $payload);
        $this->assertArrayNotHasKey('callback_url', $payload);
        $this->assertArrayNotHasKey('mode', $payload);
    }

    public function test_it_creates_an_omari_order(): void
    {
        $this->integration->omari([
            'order_id' => 'order-3',
            'sender' => '0263780070488',
            'amount' => 10,
            'currency' => 'USD',
            'omari_number' => '263780000000',
        ]);

        $payload = $this->client->orders[0];

        $this->assertSame('omari', $payload['payment_method']);
        $this->assertSame('263780000000', $payload['payment_details']['omari_number']);
    }

    public function test_it_creates_a_zimswitch_order_with_a_callback(): void
    {
        $this->integration->zimswitch([
            'order_id' => 'order-4',
            'sender' => '+263780070488',
            'amount' => 10,
            'currency' => 'USD',
        ]);

        $payload = $this->client->orders[0];

        $this->assertSame('zimswitch', $payload['payment_method']);
        $this->assertSame('https://example.com/callback', $payload['callback_url']);
        $this->assertArrayNotHasKey('redirect_url', $payload);
        $this->assertArrayNotHasKey('mode', $payload);
    }

    public function test_it_omits_the_zimswitch_callback_when_not_configured(): void
    {
        $config = new DischubConfig([
            'api_key' => 'secret',
            'recipient' => 'merchant@example.com',
        ]);

        $client = new FakeDischubClient($config);
        $integration = new NonRedirectIntegration($client, new PaymentValidator(), $config);

        $integration->zimswitch([
            'order_id' => 'order-4',
            'sender' => '+263780070488',
            'amount' => 10,
            'currency' => 'USD',
        ]);

        $this->assertArrayNotHasKey('callback_url', $client->orders[0]);
    }

    public function test_it_requires_ecocash_details(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->integration->ecocash([
            'order_id' => 'order-1',
            'sender' => '+263780070488',
            'amount' => 10,
            'currency' => 'USD',
        ]);
    }

    public function test_it_requires_omari_details(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->integration->omari([
            'order_id' => 'order-1',
            'sender' => '+263780070488',
            'amount' => 10,
            'currency' => 'USD',
        ]);
    }

    public function test_it_rejects_invalid_ecocash_numbers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->integration->ecocash([
            'order_id' => 'order-1',
            'sender' => '+263780070488',
            'amount' => 10,
            'currency' => 'USD',
            'ecocash_number' => '263780000000',
        ]);
    }

    public function test_it_rejects_invalid_omari_numbers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->integration->omari([
            'order_id' => 'order-1',
            'sender' => '+263780070488',
            'amount' => 10,
            'currency' => 'USD',
            'omari_number' => '+263780000000',
        ]);
    }

    public function test_it_rejects_unsupported_payment_methods(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->integration->createOrder('card', [
            'order_id' => 'order-1',
            'sender' => '+263780070488',
            'amount' => 10,
            'currency' => 'USD',
        ]);
    }

    public function test_it_gets_the_standard_payment_status(): void
    {
        $this->integration->getPaymentStatus('order-1');

        $this->assertSame([
            'order_id' => 'order-1',
            'recipient' => 'merchant@example.com',
        ], $this->client->statuses[0]);
    }

    public function test_it_gets_the_omari_payment_status_with_an_otp(): void
    {
        $this->integration->validateOmariOtp('order-3', '234233');

        $this->assertSame([
            'order_id' => 'order-3',
            'recipient' => 'merchant@example.com',
            'payment_method' => 'omari',
            'payment_details' => [
                'omari_otp' => '234233',
            ],
        ], $this->client->statuses[0]);
    }

    public function test_it_builds_the_zimswitch_widget_url(): void
    {
        $this->assertSame(
            'https://dischub.co.zw/api/zimswitch/non/redirect/payment/to/order-4',
            $this->integration->getZimswitchWidgetUrl('order-4')
        );
    }

    public function test_it_lists_the_supported_payment_methods(): void
    {
        $this->assertSame(
            ['ecocash', 'innbucks', 'omari', 'zimswitch'],
            NonRedirectIntegration::paymentMethods()
        );
    }
}
