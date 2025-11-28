<?php
declare(strict_types=1);

namespace Prosa\Orders\Infrastructure\Import\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Prosa\Orders\Domain\Catalog\CatalogRepository;

class GenericExcelOrderImporter
{
    /**
     * @var CatalogRepository
     */
    private $catalogRepository;

    public function __construct(CatalogRepository $catalogRepository)
    {
        $this->catalogRepository = $catalogRepository;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return [];
        }

        $headerRow = array_shift($rows);
        $sampleRows = array_slice($rows, 0, 20);
        $headerValues = array_values($headerRow);

        $articleColumn = $this->catalogRepository->detectArticleColumn($headerValues, array_map('array_values', $sampleRows));
        $storeColumn = $this->detectColumnByName($headerValues, ['TIENDA', 'SUCURSAL'], 0);
        $quantityColumn = $this->detectColumnByName($headerValues, ['CANT', 'CANTIDAD'], 1);
        $priceColumn = $this->detectColumnByName($headerValues, ['COSTO', 'PRECIO'], 2);

        $lines = [];
        foreach ($rows as $row) {
            $values = array_values($row);
            $lines[] = [
                'store'       => isset($values[$storeColumn]) ? trim((string) $values[$storeColumn]) : '000',
                'rawArticle'  => isset($values[$articleColumn]) ? trim((string) $values[$articleColumn]) : '',
                'quantity'    => isset($values[$quantityColumn]) ? (float) $values[$quantityColumn] : 0.0,
                'priceClient' => isset($values[$priceColumn]) ? (float) $values[$priceColumn] : 0.0,
            ];
        }

        return $lines;
    }

    private function detectColumnByName(array $headerRow, array $keywords, int $default): int
    {
        foreach ($headerRow as $index => $value) {
            $upper = strtoupper((string) $value);
            foreach ($keywords as $keyword) {
                if (strpos($upper, $keyword) !== false) {
                    return $index;
                }
            }
        }
        // fallback documentado
        return $default;
    }
}
