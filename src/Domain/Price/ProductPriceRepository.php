<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Price;

interface ProductPriceRepository
{
    public function getPriceForProduct(string $productCode, int $priceList): float;
}
