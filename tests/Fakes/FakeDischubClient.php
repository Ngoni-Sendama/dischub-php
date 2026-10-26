<?php

namespace Ngoni\DischubPhp\Tests\Fakes;

use Ngoni\DischubPhp\Support\DischubClient;

class FakeDischubClient extends DischubClient
{
    public array $orders = [];

    public array $statuses = [];

    public function createOrder(array $payload): array
    {
        $this->orders[] = $payload;

        return [
            'status' => 'success',
            'response_code' => 201,
        ];
    }

    public function getPaymentStatus(array $payload): array
    {
        $this->statuses[] = $payload;

        return [
            'status' => 'success',
        ];
    }
}
