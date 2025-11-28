<?php
declare(strict_types=1);

namespace Prosa\Orders\Application\UseCase\GenerateModForStore;

class GenerateModForStoreCommand
{
    /**
     * @var string
     */
    private $store;

    public function __construct(string $store)
    {
        $this->store = $store;
    }

    public function store(): string
    {
        return $this->store;
    }
}
