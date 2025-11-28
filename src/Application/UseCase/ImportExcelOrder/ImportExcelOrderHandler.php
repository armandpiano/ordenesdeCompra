<?php
declare(strict_types=1);

namespace Prosa\Orders\Application\UseCase\ImportExcelOrder;

use Prosa\Orders\Application\Dto\OrderPreviewDto;
use Prosa\Orders\Application\Dto\OrderPreviewLineDto;
use Prosa\Orders\Application\Dto\StorePreviewDto;
use Prosa\Orders\Domain\Catalog\CatalogRepository;
use Prosa\Orders\Domain\Client\Client;
use Prosa\Orders\Domain\Client\ClientId;
use Prosa\Orders\Domain\Client\ClientRepository;
use Prosa\Orders\Domain\Price\ProductPriceRepository;
use Prosa\Orders\Domain\Product\ProductRepository;
use Prosa\Orders\Infrastructure\Import\Excel\GenericExcelOrderImporter;

class ImportExcelOrderHandler
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

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

    /**
     * @var GenericExcelOrderImporter
     */
    private $importer;

    /**
     * @var float
     */
    private $minSimilarity;

    public function __construct(
        ClientRepository $clientRepository,
        CatalogRepository $catalogRepository,
        ProductRepository $productRepository,
        ProductPriceRepository $priceRepository,
        GenericExcelOrderImporter $importer,
        float $minSimilarity = 80.0
    ) {
        $this->clientRepository = $clientRepository;
        $this->catalogRepository = $catalogRepository;
        $this->productRepository = $productRepository;
        $this->priceRepository = $priceRepository;
        $this->importer = $importer;
        $this->minSimilarity = $minSimilarity;
    }

    public function __invoke(ImportExcelOrderCommand $command): OrderPreviewDto
    {
        $clientId = new ClientId($command->clientCode());
        $client = $this->clientRepository->findById($clientId);
        if ($client === null) {
            // Creamos cliente genérico para permitir pruebas locales cuando no hay conexión a Firebird.
            $client = new Client($clientId, 'CLIENTE DESCONOCIDO', 1);
        }

        $rawLines = $this->importer->import($command->excelFilePath());

        $stores = [];
        foreach ($rawLines as $index => $rawLine) {
            $catalogCode = null;
            $matchType = 'unmapped';
            if (preg_match('/^\d{5}$/', trim($rawLine['rawArticle']))) {
                $catalogArticle = $this->catalogRepository->findByCode(trim($rawLine['rawArticle']));
                if ($catalogArticle !== null) {
                    $catalogCode = $catalogArticle->code();
                    $matchType = 'catalog_code';
                }
            }

            if ($catalogCode === null) {
                $article = $this->catalogRepository->findBestMatchByDescription((string) $rawLine['rawArticle'], $this->minSimilarity);
                if ($article !== null) {
                    $catalogCode = $article->code();
                    $matchType = 'catalog_match';
                }
            }

            $productFound = false;
            $description = (string) $rawLine['rawArticle'];
            $priceSae = 0.0;
            if ($catalogCode !== null) {
                $product = $this->productRepository->findByCode($catalogCode);
                if ($product !== null) {
                    $productFound = true;
                    $description = $product->description();
                    $priceSae = $this->priceRepository->getPriceForProduct($catalogCode, $client->priceList());
                }
            }

            $quantity = (float) $rawLine['quantity'];
            $priceCliente = (float) $rawLine['priceClient'];
            $importeCliente = $quantity * $priceCliente;
            $importeSae = $quantity * $priceSae;
            $storeKey = (string) $rawLine['store'];

            $lineDto = new OrderPreviewLineDto(
                $storeKey,
                $index,
                $catalogCode,
                $description,
                $quantity,
                $priceCliente,
                $priceSae,
                $importeCliente,
                $importeSae,
                $productFound,
                $matchType
            );

            if (!isset($stores[$storeKey])) {
                $stores[$storeKey] = [];
            }
            $stores[$storeKey][] = $lineDto;
        }

        $storeDtos = [];
        foreach ($stores as $storeKey => $lines) {
            $totalClient = 0.0;
            $totalSae = 0.0;
            foreach ($lines as $line) {
                /** @var OrderPreviewLineDto $line */
                $totalClient += $line->importeCliente();
                $totalSae += $line->importeSae();
            }
            $storeDtos[$storeKey] = new StorePreviewDto($storeKey, $lines, $totalClient, $totalSae);
        }

        $storeKeys = array_keys($storeDtos);
        return new OrderPreviewDto($client, $storeDtos, $storeKeys, [], null);
    }
}
