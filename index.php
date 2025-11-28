<?php
declare(strict_types=1);

use Prosa\Orders\Infrastructure\Http\Controllers\OrderController;

require __DIR__ . '/vendor/autoload.php';

session_start();

$controller = new OrderController();
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'upload-excel-form':
        $controller->showUploadExcelForm();
        break;
    case 'import-excel':
        $controller->importExcel();
        break;
    case 'select-store':
        $controller->selectStore();
        break;
    case 'update-line':
        $controller->updateLine();
        break;
    case 'generate-mod':
        $controller->generateModForStore();
        break;
    default:
        $controller->home();
        break;
}
