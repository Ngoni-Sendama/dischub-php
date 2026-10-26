<?php

namespace Ngoni\DischubPhp\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Ngoni\DischubPhp\DischubService;
use Ngoni\DischubPhp\Support\DischubConfig;

final class ServiceProviderTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('dischub', [
            'api_key' => 'test-key',
            'recipient' => 'merchant@example.com',
            'callback_url' => 'https://example.com/callback',
            'redirect_url' => 'https://example.com/complete',
            'mode' => 'test',
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);
    }

    public function test_it_registers_singletons(): void
    {
        $service = $this->app->make(DischubService::class);

        $this->assertSame($service, $this->app->make(DischubService::class));
        $this->assertSame($service, $this->app->make('dischub'));
        $this->assertSame(
            $this->app->make(DischubConfig::class),
            $this->app->make(DischubConfig::class)
        );
    }

    public function test_it_reads_configuration_from_the_container(): void
    {
        $config = $this->app->make(DischubConfig::class);

        $this->assertSame('test-key', $config->apiKey());
        $this->assertSame('merchant@example.com', $config->recipient());
        $this->assertSame('https://example.com/callback', $config->callbackUrl());
        $this->assertSame('https://example.com/complete', $config->redirectUrl());
        $this->assertSame('test', $config->mode());
        $this->assertSame(15.0, $config->timeout());
        $this->assertSame(5.0, $config->connectTimeout());
    }

    public function test_redirect_requests_use_the_api_key_header(): void
    {
        Http::fake([
            'https://dischub.co.zw/api/orders/create/' => Http::response([
                'status' => 'success',
                'message' => 'order created successfully',
                'response_code' => 201,
            ], 201),
        ]);

        $response = $this->app->make(DischubService::class)->redirect()->createOrder([
            'order_id' => 'order-1',
            'sender' => '+263780070488',
            'amount' => 14.58,
            'currency' => 'USD',
        ]);

        $this->assertSame('success', $response['status']);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://dischub.co.zw/api/orders/create/'
                && $request->hasHeader('X-Api-Key', 'test-key')
                && $request->hasHeader('Accept', 'application/json')
                && $request['order_id'] === 'order-1'
                && $request['recipient'] === 'merchant@example.com'
                && $request['mode'] === 'test'
                && $request['callback_url'] === 'https://example.com/callback'
                && $request['redirect_url'] === 'https://example.com/complete';
        });
    }

    public function test_non_redirect_requests_do_not_send_redirect_fields(): void
    {
        Http::fake([
            'https://dischub.co.zw/api/orders/create/' => Http::response([
                'status' => 'success',
                'response_code' => 201,
            ], 201),
        ]);

        $this->app->make(DischubService::class)->nonRedirect()->ecocash([
            'order_id' => 'order-2',
            'sender' => '0780070488',
            'amount' => 10,
            'currency' => 'USD',
            'ecocash_number' => '+263780000000',
        ]);

        Http::assertSent(function (Request $request) {
            return $request['payment_method'] === 'ecocash'
                && $request['payment_details']['ecocash_number'] === '+263780000000'
                && ! isset($request['mode'])
                && ! isset($request['callback_url'])
                && ! isset($request['redirect_url']);
        });
    }

    public function test_it_returns_a_structured_error_for_non_json_responses(): void
    {
        Http::fake([
            'https://dischub.co.zw/api/payment/status/3/step/' => Http::response('gateway error', 502),
        ]);

        $response = $this->app->make(DischubService::class)->redirect()->getPaymentStatus('order-1');

        $this->assertSame('error', $response['status']);
        $this->assertSame('gateway error', $response['message']);
        $this->assertSame(502, $response['response_code']);
    }

    public function test_it_returns_connection_errors_as_structured_responses(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('connection failed');
        });

        $response = $this->app->make(DischubService::class)->redirect()->getPaymentStatus('order-1');

        $this->assertSame('error', $response['status']);
        $this->assertSame('connection failed', $response['message']);
        $this->assertSame(0, $response['response_code']);
    }
}
