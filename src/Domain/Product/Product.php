<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Product;

class Product
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $description;

    public function __construct(string $code, string $description)
    {
        $this->code = $code;
        $this->description = $description;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function description(): string
    {
        return $this->description;
    }
}
