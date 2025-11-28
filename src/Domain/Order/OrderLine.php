<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Order;

class OrderLine
{
    /**
     * @var string
     */
    private $productCode;

    /**
     * @var string
     */
    private $description;

    /**
     * @var float
     */
    private $quantity;

    /**
     * @var float
     */
    private $price;

    public function __construct(string $productCode, string $description, float $quantity, float $price)
    {
        $this->productCode = $productCode;
        $this->description = $description;
        $this->quantity = $quantity;
        $this->price = $price;
    }

    public function productCode(): string
    {
        return $this->productCode;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    public function price(): float
    {
        return $this->price;
    }
}
