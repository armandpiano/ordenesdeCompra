<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Client;

interface ClientRepository
{
    /**
     * @return Client|null
     */
    public function findById(ClientId $id);

    /**
     * @return Client[]
     */
    public function listAll(): array;
}
