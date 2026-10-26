<?php

namespace Ngoni\DischubPhp\Tests\Support;

use InvalidArgumentException;
use Ngoni\DischubPhp\Support\PaymentValidator;
use Ngoni\DischubPhp\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class PaymentValidatorTest extends TestCase
{
    private PaymentValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PaymentValidator();
    }

    public function test_it_casts_numeric_order_ids_to_strings(): void
    {
        $this->assertSame('344245663', $this->validator->orderId(344245663));
    }

    public function test_it_rejects_order_ids_longer_than_30_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->orderId(str_repeat('a', 31));
    }

    public function test_it_rejects_non_string_order_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->orderId(['not-a-string']);
    }

    #[DataProvider('validSenderProvider')]
    public function test_it_accepts_valid_sender_formats(string $sender): void
    {
        $this->assertSame($sender, $this->validator->sender($sender));
    }

    public static function validSenderProvider(): array
    {
        return [
            ['+263780070488'],
            ['0263780070488'],
            ['0780070488'],
        ];
    }

    public function test_it_rejects_invalid_sender_formats(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->sender('not-a-phone');
    }

    public function test_it_accepts_amounts_within_the_api_range(): void
    {
        $this->assertSame(14.58, $this->validator->amount('14.58'));
        $this->assertSame(1.0, $this->validator->amount(1));
    }

    #[DataProvider('invalidAmountProvider')]
    public function test_it_rejects_invalid_amounts($amount): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->amount($amount);
    }

    public static function invalidAmountProvider(): array
    {
        return [
            [0],
            [-1],
            [100000],
            ['not-a-number'],
        ];
    }

    public function test_it_uppercases_currency_codes(): void
    {
        $this->assertSame('USD', $this->validator->currency('usd'));
        $this->assertSame('ZWG', $this->validator->currency('ZWG'));
    }

    public function test_it_rejects_unsupported_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->currency('EUR');
    }

    public function test_it_accepts_test_and_live_modes(): void
    {
        $this->assertSame('test', $this->validator->mode('TEST'));
        $this->assertSame('live', $this->validator->mode('live'));
    }

    public function test_it_rejects_unknown_modes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->mode('sandbox');
    }

    public function test_it_requires_https_for_callbacks(): void
    {
        $this->assertSame(
            'https://example.com/callback',
            $this->validator->callbackUrl('https://example.com/callback')
        );
        $this->assertNull($this->validator->callbackUrl(null));
        $this->assertNull($this->validator->callbackUrl(''));
    }

    public function test_it_rejects_insecure_callbacks(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->callbackUrl('http://example.com/callback');
    }

    public function test_it_accepts_http_and_https_redirects(): void
    {
        $this->assertSame(
            'https://example.com/complete',
            $this->validator->redirectUrl('https://example.com/complete')
        );
        $this->assertSame(
            'http://example.com/complete',
            $this->validator->redirectUrl('http://example.com/complete')
        );
    }

    public function test_it_rejects_unsupported_redirect_schemes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->redirectUrl('ftp://example.com/complete');
    }

    #[DataProvider('paymentMethodProvider')]
    public function test_it_accepts_supported_payment_methods(string $method): void
    {
        $this->assertSame($method, $this->validator->paymentMethod(strtoupper($method)));
    }

    public static function paymentMethodProvider(): array
    {
        return [
            ['ecocash'],
            ['innbucks'],
            ['omari'],
            ['zimswitch'],
        ];
    }

    public function test_it_rejects_unsupported_payment_methods(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->paymentMethod('card');
    }

    public function test_it_reads_required_strings_from_arrays(): void
    {
        $this->assertSame('value', $this->validator->requiredString(['key' => 'value'], 'key'));
    }

    public function test_it_rejects_missing_required_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->requiredString([], 'key');
    }
}
