<?php
declare(strict_types=1);

namespace Prosa\Orders\Application\UseCase\GenerateModForStore;

use Prosa\Orders\Application\Dto\OrderPreviewDto;
use Prosa\Orders\Application\Dto\OrderPreviewLineDto;
use Prosa\Orders\Domain\Mod\ModFile;
use Prosa\Orders\Domain\Mod\ModFileBuilder;
use Prosa\Orders\Domain\Order\Order;
use Prosa\Orders\Domain\Order\OrderLine;

class GenerateModForStoreHandler
{
    /**
     * @var ModFileBuilder
     */
    private $modFileBuilder;

    public function __construct(ModFileBuilder $modFileBuilder)
    {
        $this->modFileBuilder = $modFileBuilder;
    }

    /**
     * @return array{0: ModFile, 1: OrderPreviewDto}
     */
    public function __invoke(GenerateModForStoreCommand $command, OrderPreviewDto $preview): array
    {
        $storeKey = $command->store();
        $stores = $preview->stores();
        if (!isset($stores[$storeKey])) {
            throw new \InvalidArgumentException('Tienda no encontrada en la vista previa.');
        }

        $lines = $stores[$storeKey]->lines();
        $validLines = [];
        foreach ($lines as $line) {
            /** @var OrderPreviewLineDto $line */
            if ($line->productCode() !== null && $line->productFound()) {
                $validLines[] = new OrderLine(
                    $line->productCode(),
                    $line->description(),
                    $line->quantity(),
                    $line->priceSae()
                );
            }
        }

        $order = new Order($preview->client(), $storeKey, $validLines);
        $modFile = $this->modFileBuilder->buildFromOrder($order);

        $preview->markStoreCompleted($storeKey);
        $updatedPreview = new OrderPreviewDto(
            $preview->client(),
            $preview->stores(),
            $preview->storesPending(),
            $preview->storesCompleted(),
            null
        );

        return [$modFile, $updatedPreview];
    }
}
