<?php
declare(strict_types=1);

namespace Prosa\Orders\Infrastructure\Http\Controllers;

use Prosa\Orders\Application\UseCase\GenerateModForStore\GenerateModForStoreCommand;
use Prosa\Orders\Application\UseCase\GenerateModForStore\GenerateModForStoreHandler;
use Prosa\Orders\Application\UseCase\ImportExcelOrder\ImportExcelOrderCommand;
use Prosa\Orders\Application\UseCase\ImportExcelOrder\ImportExcelOrderHandler;
use Prosa\Orders\Application\UseCase\UpdateOrderLine\UpdateOrderLineCommand;
use Prosa\Orders\Application\UseCase\UpdateOrderLine\UpdateOrderLineHandler;
use Prosa\Orders\Infrastructure\Catalog\TxtCatalogRepository;
use Prosa\Orders\Infrastructure\Import\Excel\GenericExcelOrderImporter;
use Prosa\Orders\Infrastructure\Mod\AspelXmlModFileBuilder;
use Prosa\Orders\Infrastructure\Persistence\Firebird\FirebirdClientRepository;
use Prosa\Orders\Infrastructure\Persistence\Firebird\FirebirdConnection;
use Prosa\Orders\Infrastructure\Persistence\Firebird\FirebirdProductPriceRepository;
use Prosa\Orders\Infrastructure\Persistence\Firebird\FirebirdProductRepository;

class OrderController
{
    public function home(): void
    {
        $this->render('home.php');
    }

    public function showUploadExcelForm(): void
    {
        $this->render('upload_excel.php');
    }

    public function importExcel(): void
    {
        if (!isset($_POST['clientCode']) || empty($_FILES['order_excel']['name'])) {
            $error = 'Debes capturar el cliente y seleccionar un archivo.';
            $this->render('upload_excel.php', ['error' => $error]);
            return;
        }

        $clientCode = trim((string) $_POST['clientCode']);
        $paths = require __DIR__ . '/../../../../config/paths.php';
        $uploadsDir = $paths['uploads_dir'];
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        $fileInfo = pathinfo($_FILES['order_excel']['name']);
        $ext = strtolower(isset($fileInfo['extension']) ? $fileInfo['extension'] : '');
        $allowed = ['xls', 'xlsx', 'xlsm', 'ods', 'csv'];
        if (!in_array($ext, $allowed, true)) {
            $error = 'Extensión no permitida.';
            $this->render('upload_excel.php', ['error' => $error]);
            return;
        }

        $targetFile = rtrim($uploadsDir, '/') . '/' . uniqid('oc_', true) . '.' . $ext;
        if (!move_uploaded_file($_FILES['order_excel']['tmp_name'], $targetFile)) {
            $error = 'No se pudo guardar el archivo subido.';
            $this->render('upload_excel.php', ['error' => $error]);
            return;
        }

        $dependencies = $this->buildDependencies();

        $command = new ImportExcelOrderCommand($clientCode, $targetFile);
        $handler = new ImportExcelOrderHandler(
            $dependencies['clientRepository'],
            $dependencies['catalogRepository'],
            $dependencies['productRepository'],
            $dependencies['priceRepository'],
            $dependencies['excelImporter']
        );

        $orderPreview = $handler($command);
        $_SESSION['order_preview'] = serialize($orderPreview);

        $this->render('order_preview.php', ['preview' => $orderPreview, 'message' => null]);
    }

    public function selectStore(): void
    {
        if (!isset($_SESSION['order_preview'])) {
            header('Location: /index.php');
            return;
        }
        $preview = unserialize($_SESSION['order_preview']);
        $store = isset($_POST['store']) ? (string) $_POST['store'] : '';
        $preview->selectStore($store);
        $_SESSION['order_preview'] = serialize($preview);
        $this->render('order_preview.php', ['preview' => $preview, 'message' => null]);
    }

    public function updateLine(): void
    {
        if (!isset($_SESSION['order_preview'])) {
            header('Location: /index.php');
            return;
        }
        $preview = unserialize($_SESSION['order_preview']);
        $store = isset($_POST['store']) ? (string) $_POST['store'] : '';
        $index = isset($_POST['line_index']) ? $_POST['line_index'] : 0;
        $newCode = isset($_POST['new_product_code']) ? (string) $_POST['new_product_code'] : '';

        $dependencies = $this->buildDependencies();
        $handler = new UpdateOrderLineHandler(
            $dependencies['catalogRepository'],
            $dependencies['productRepository'],
            $dependencies['priceRepository']
        );

        list($updatedPreview, $message) = $handler(new UpdateOrderLineCommand($store, $index, $newCode), $preview);
        $_SESSION['order_preview'] = serialize($updatedPreview);
        $this->render('order_preview.php', ['preview' => $updatedPreview, 'message' => $message]);
    }

    public function generateModForStore(): void
    {
        if (!isset($_SESSION['order_preview'])) {
            header('Location: /index.php');
            return;
        }
        $preview = unserialize($_SESSION['order_preview']);
        $store = isset($_POST['store']) ? (string) $_POST['store'] : '';

        $dependencies = $this->buildDependencies();
        $handler = new GenerateModForStoreHandler(new AspelXmlModFileBuilder());
        list($modFile, $updatedPreview) = $handler(new GenerateModForStoreCommand($store), $preview);

        $_SESSION['order_preview'] = serialize($updatedPreview);

        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $modFile->getFileName() . '"');
        echo $modFile->getContents();
        exit;
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../Views/' . $view;
        include __DIR__ . '/../Views/layout.php';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDependencies(): array
    {
        $paths = require __DIR__ . '/../../../../config/paths.php';
        $firebirdConfig = require __DIR__ . '/../../../../config/firebird.php';

        $catalogRepository = new TxtCatalogRepository($paths['catalog_file']);
        $excelImporter = new GenericExcelOrderImporter($catalogRepository);

        try {
            $connection = new FirebirdConnection($firebirdConfig);
            $clientRepository = new FirebirdClientRepository($connection, $firebirdConfig['empresa']);
            $productRepository = new FirebirdProductRepository($connection, $firebirdConfig['empresa']);
            $priceRepository = new FirebirdProductPriceRepository($connection, $firebirdConfig['empresa']);
        } catch (\RuntimeException $e) {
            // Fallback simple sin conexión.
            $clientRepository = new class implements \Prosa\Orders\Domain\Client\ClientRepository {
                public function findById(\Prosa\Orders\Domain\Client\ClientId $id)
                {
                    return null;
                }
            };
            $productRepository = new class implements \Prosa\Orders\Domain\Product\ProductRepository {
                public function findByCode(string $code)
                {
                    return null;
                }
            };
            $priceRepository = new class implements \Prosa\Orders\Domain\Price\ProductPriceRepository {
                public function getPriceForProduct(string $productCode, int $priceList): float
                {
                    return 0.0;
                }
            };
        }

        return [
            'catalogRepository' => $catalogRepository,
            'excelImporter' => $excelImporter,
            'clientRepository' => $clientRepository,
            'productRepository' => $productRepository,
            'priceRepository' => $priceRepository,
        ];
    }
}
