<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Product;

interface ProductRepository
{
    /**
     * @return Product|null
     */
    public function findByCode(string $code);
}
