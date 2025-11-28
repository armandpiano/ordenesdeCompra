<?php
declare(strict_types=1);

namespace Prosa\Orders\Infrastructure\Persistence\Firebird;

use Prosa\Orders\Domain\Client\Client;
use Prosa\Orders\Domain\Client\ClientId;
use Prosa\Orders\Domain\Client\ClientRepository;

class FirebirdClientRepository implements ClientRepository
{
    /**
     * @var FirebirdConnection
     */
    private $connection;

    /**
     * @var string
     */
    private $empresa;

    public function __construct(FirebirdConnection $connection, string $empresa)
    {
        $this->connection = $connection;
        $this->empresa = $empresa;
    }

    public function findById(ClientId $id): ?Client
    {
        $sql = 'SELECT FIRST 1 CLAVE, NOMBRE, LISTA_PREC FROM CLIE' . $this->empresa . ' WHERE CLAVE = ?';
        $result = $this->connection->query($sql, [$id->padded()]);
        $row = $this->connection->fetchAssoc($result);
        if ($row === null) {
            return null;
        }

        return new Client($id, $row['NOMBRE'], (int) $row['LISTA_PREC']);
    }

    /**
     * @return Client[]
     */
    public function listAll(): array
    {
        $sql = 'SELECT CLAVE, NOMBRE, LISTA_PREC FROM CLIE' . $this->empresa . ' ORDER BY CLAVE';
        $result = $this->connection->query($sql);

        $clients = [];
        while ($row = $this->connection->fetchAssoc($result)) {
            $id = new ClientId($row['CLAVE']);
            $clients[] = new Client($id, $row['NOMBRE'], (int) $row['LISTA_PREC']);
        }

        return $clients;
    }
}
