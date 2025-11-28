<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Client;

class ClientId
{
    /**
     * @var string
     */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function padded(): string
    {
        return str_pad($this->value, 10, ' ', STR_PAD_LEFT);
    }
}
