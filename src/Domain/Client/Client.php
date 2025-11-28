<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Client;

class Client
{
    /**
     * @var ClientId
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $priceList;

    public function __construct(ClientId $id, string $name, int $priceList)
    {
        $this->id = $id;
        $this->name = $name;
        $this->priceList = $priceList;
    }

    public function id(): ClientId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function priceList(): int
    {
        return $this->priceList;
    }
}
