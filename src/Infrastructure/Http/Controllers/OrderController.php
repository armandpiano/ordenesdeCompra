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
        if (empty($_FILES['order_excel']['name'])) {
            $error = 'Debes seleccionar un archivo.';
            $this->render('upload_excel.php', ['error' => $error]);
            return;
        }

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

        $rawLines = $dependencies['excelImporter']->import($targetFile);
        $storesSummary = [];
        foreach ($rawLines as $rawLine) {
            $storeKey = isset($rawLine['store']) ? (string) $rawLine['store'] : 'TIENDA SIN NOMBRE';
            if ($storeKey === '') {
                $storeKey = 'TIENDA SIN NOMBRE';
            }
            if (!isset($storesSummary[$storeKey])) {
                $storesSummary[$storeKey] = 0;
            }
            $storesSummary[$storeKey] += 1;
        }

        $_SESSION['uploaded_excel_path'] = $targetFile;
        $_SESSION['stores_summary'] = $storesSummary;
        $_SESSION['stores_pending'] = array_keys($storesSummary);
        $_SESSION['stores_completed'] = [];
        unset($_SESSION['order_preview']);
        $_SESSION['last_client_code'] = '';

        $clients = $dependencies['clientRepository']->listAll();

        $this->render('order_preview.php', [
            'preview' => null,
            'message' => null,
            'storesSummary' => $storesSummary,
            'lastClientCode' => '',
            'clients' => $clients,
        ]);
    }

    public function prepareStorePreview(): void
    {
        if (!isset($_SESSION['uploaded_excel_path'])) {
            header('Location: /index.php');
            return;
        }

        $clientCode = isset($_POST['clientCode']) ? trim((string) $_POST['clientCode']) : '';
        $store = isset($_POST['store']) ? (string) $_POST['store'] : '';

        $dependencies = $this->buildDependencies();
        $clients = $dependencies['clientRepository']->listAll();

        if ($clientCode === '' || $store === '') {
            $storesSummary = isset($_SESSION['stores_summary']) ? (array) $_SESSION['stores_summary'] : [];
            $message = 'Debes capturar cliente y seleccionar una tienda.';
            $this->render('order_preview.php', [
                'preview' => null,
                'message' => $message,
                'storesSummary' => $storesSummary,
                'lastClientCode' => $clientCode,
                'clients' => $clients,
            ]);
            return;
        }

        $_SESSION['last_client_code'] = $clientCode;

        $command = new ImportExcelOrderCommand(
            $clientCode,
            (string) $_SESSION['uploaded_excel_path'],
            $store,
            isset($_SESSION['stores_pending']) ? (array) $_SESSION['stores_pending'] : [],
            isset($_SESSION['stores_completed']) ? (array) $_SESSION['stores_completed'] : []
        );
        $handler = new ImportExcelOrderHandler(
            $dependencies['clientRepository'],
            $dependencies['catalogRepository'],
            $dependencies['productRepository'],
            $dependencies['priceRepository'],
            $dependencies['excelImporter']
        );

        $orderPreview = $handler($command);
        $orderPreview->selectStore($store);
        $_SESSION['order_preview'] = serialize($orderPreview);

        $storesSummary = isset($_SESSION['stores_summary']) ? (array) $_SESSION['stores_summary'] : [];
        $this->render('order_preview.php', [
            'preview' => $orderPreview,
            'message' => null,
            'storesSummary' => $storesSummary,
            'lastClientCode' => $clientCode,
            'clients' => $clients,
        ]);
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
        $storesSummary = isset($_SESSION['stores_summary']) ? (array) $_SESSION['stores_summary'] : [];
        $lastClientCode = isset($_SESSION['last_client_code']) ? (string) $_SESSION['last_client_code'] : '';
        $clients = $dependencies['clientRepository']->listAll();
        $this->render('order_preview.php', [
            'preview' => $updatedPreview,
            'message' => $message,
            'storesSummary' => $storesSummary,
            'lastClientCode' => $lastClientCode,
            'clients' => $clients,
        ]);
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
        $_SESSION['stores_pending'] = $updatedPreview->storesPending();
        $_SESSION['stores_completed'] = $updatedPreview->storesCompleted();

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

                public function listAll(): array
                {
                    return [];
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
