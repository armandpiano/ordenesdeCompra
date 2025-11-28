<?php
declare(strict_types=1);

namespace Prosa\Orders\Application\UseCase\UpdateOrderLine;

use Prosa\Orders\Application\Dto\OrderPreviewDto;
use Prosa\Orders\Application\Dto\OrderPreviewLineDto;
use Prosa\Orders\Domain\Catalog\CatalogRepository;
use Prosa\Orders\Domain\Price\ProductPriceRepository;
use Prosa\Orders\Domain\Product\ProductRepository;

class UpdateOrderLineHandler
{
    /**
     * @var CatalogRepository
     */
    private $catalogRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ProductPriceRepository
     */
    private $priceRepository;

    public function __construct(
        CatalogRepository $catalogRepository,
        ProductRepository $productRepository,
        ProductPriceRepository $priceRepository
    ) {
        $this->catalogRepository = $catalogRepository;
        $this->productRepository = $productRepository;
        $this->priceRepository = $priceRepository;
    }

    /**
     * @return array{0: OrderPreviewDto, 1: string|null}
     */
    public function __invoke(UpdateOrderLineCommand $command, OrderPreviewDto $preview): array
    {
        $message = null;
        $storeKey = $command->store();
        $lineIndex = $command->lineIndex();
        $stores = $preview->stores();
        if (!isset($stores[$storeKey])) {
            return [$preview, 'Tienda no encontrada'];
        }

        $lines = $stores[$storeKey]->lines();
        if (!isset($lines[$lineIndex])) {
            return [$preview, 'Línea no encontrada'];
        }

        $catalogArticle = $this->catalogRepository->findByCode($command->newProductCode());
        if ($catalogArticle === null) {
            $message = 'La clave no existe en el catálogo base.';
            return [$preview, $message];
        }

        $product = $this->productRepository->findByCode($catalogArticle->code());
        if ($product === null) {
            $message = 'La clave no existe en SAE.';
            return [$preview, $message];
        }

        /** @var OrderPreviewLineDto $line */
        $line = $lines[$lineIndex];
        $line->setProductCode($product->code());
        $line->setDescription($product->description());
        $priceSae = $this->priceRepository->getPriceForProduct($product->code(), $preview->client()->priceList());
        $line->setPriceSae($priceSae);
        $line->setImporteSae($priceSae * $line->quantity());
        $line->setProductFound(true);
        $line->setMatchType('manual_update');

        $lines[$lineIndex] = $line;
        $stores[$storeKey]->setLines($lines);
        $stores[$storeKey]->recalculateTotals();

        $updatedPreview = new OrderPreviewDto(
            $preview->client(),
            $stores,
            $preview->storesPending(),
            $preview->storesCompleted(),
            $storeKey
        );

        return [$updatedPreview, $message];
    }
}
