<?php
declare(strict_types=1);

namespace Prosa\Orders\Infrastructure\Persistence\Firebird;

use Prosa\Orders\Domain\Product\Product;
use Prosa\Orders\Domain\Product\ProductRepository;

class FirebirdProductRepository implements ProductRepository
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

    public function findByCode(string $code): ?Product
    {
        $sql = 'SELECT FIRST 1 CVE_ART, DESCR FROM INVE' . $this->empresa . ' WHERE CVE_ART = ?';
        $result = $this->connection->query($sql, [$code]);
        $row = $this->connection->fetchAssoc($result);
        if ($row === null) {
            return null;
        }

        return new Product($row['CVE_ART'], $row['DESCR']);
    }
}
