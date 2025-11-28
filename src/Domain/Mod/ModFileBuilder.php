<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Mod;

use Prosa\Orders\Domain\Order\Order;

interface ModFileBuilder
{
    public function buildFromOrder(Order $order): ModFile;
}
