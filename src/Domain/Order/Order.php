<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Order;

use Prosa\Orders\Domain\Client\Client;

class Order
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $store;

    /**
     * @var OrderLine[]
     */
    private $lines = [];

    public function __construct(Client $client, string $store, array $lines)
    {
        $this->client = $client;
        $this->store = $store;
        $this->lines = $lines;
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function store(): string
    {
        return $this->store;
    }

    /**
     * @return OrderLine[]
     */
    public function lines(): array
    {
        return $this->lines;
    }
}
