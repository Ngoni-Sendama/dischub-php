<?php

namespace Ngoni\DischubPhp\Tests;

use Ngoni\DischubPhp\DischubService;
use Ngoni\DischubPhp\NonRedirect\NonRedirectIntegration;
use Ngoni\DischubPhp\Redirect\RedirectIntegration;
use Ngoni\DischubPhp\Support\DischubConfig;
use Ngoni\DischubPhp\Support\PaymentValidator;
use Ngoni\DischubPhp\Tests\Fakes\FakeDischubClient;

final class DischubServiceTest extends TestCase
{
    private FakeDischubClient $client;

    private DischubService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new DischubConfig([
            'api_key' => 'secret',
            'recipient' => 'merchant@example.com',
            'mode' => 'test',
        ]);

        $validator = new PaymentValidator();
        $this->client = new FakeDischubClient($config);

        $this->service = new DischubService(
            new RedirectIntegration($this->client, $validator, $config),
            new NonRedirectIntegration($this->client, $validator, $config)
        );
    }

    public function test_it_exposes_both_integrations(): void
    {
        $this->assertInstanceOf(RedirectIntegration::class, $this->service->redirect());
        $this->assertInstanceOf(NonRedirectIntegration::class, $this->service->nonRedirect());
        $this->assertSame($this->service->redirect(), $this->service->redirect());
        $this->assertSame($this->service->nonRedirect(), $this->service->nonRedirect());
    }

    public function test_legacy_methods_delegate_to_the_redirect_integration(): void
    {
        $this->service->createOrder([
            'order_id' => 'order-1',
            'sender' => '0780070488',
            'amount' => 10,
            'currency' => 'USD',
        ]);

        $this->service->getPaymentStatus('order-1');

        $this->assertCount(1, $this->client->orders);
        $this->assertCount(1, $this->client->statuses);
        $this->assertSame(
            'https://dischub.co.zw/api/make/payment/to/order-1',
            $this->service->getPaymentUrl('order-1')
        );
    }
}
