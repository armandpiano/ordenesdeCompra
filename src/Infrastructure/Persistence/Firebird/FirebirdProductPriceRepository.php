<?php
declare(strict_types=1);

namespace Prosa\Orders\Infrastructure\Persistence\Firebird;

use Prosa\Orders\Domain\Price\ProductPriceRepository;

class FirebirdProductPriceRepository implements ProductPriceRepository
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

    public function getPriceForProduct(string $productCode, int $priceList): float
    {
        $sql = 'SELECT FIRST 1 PRECIO FROM PRECIO_X_PROD' . $this->empresa . ' WHERE CVE_ART = ? AND CVE_PRECIO = ?';
        $result = $this->connection->query($sql, [$productCode, $priceList]);
        $row = $this->connection->fetchAssoc($result);
        if ($row === null) {
            return 0.0;
        }
        return (float) $row['PRECIO'];
    }
}
