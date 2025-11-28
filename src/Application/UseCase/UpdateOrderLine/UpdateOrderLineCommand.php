<?php
declare(strict_types=1);

namespace Prosa\Orders\Application\UseCase\UpdateOrderLine;

class UpdateOrderLineCommand
{
    /**
     * @var string
     */
    private $store;

    /**
     * @var int|string
     */
    private $lineIndex;

    /**
     * @var string
     */
    private $newProductCode;

    public function __construct(string $store, $lineIndex, string $newProductCode)
    {
        $this->store = $store;
        $this->lineIndex = $lineIndex;
        $this->newProductCode = $newProductCode;
    }

    public function store(): string
    {
        return $this->store;
    }

    public function lineIndex()
    {
        return $this->lineIndex;
    }

    public function newProductCode(): string
    {
        return $this->newProductCode;
    }
}
