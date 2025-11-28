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

        $rows = array_values(array_filter($rows, function (array $row) {
            foreach ($row as $value) {
                if (trim((string) $value) !== '') {
                    return true;
                }
            }
            return false;
        }));

        if (empty($rows)) {
            return [];
        }

        $headerRow = array_shift($rows);
        $sampleRows = array_slice($rows, 0, 20);
        $headerValues = array_values($headerRow);
        $sampleValues = array_map('array_values', $sampleRows);

        $articleColumn = $this->catalogRepository->detectArticleColumn($headerValues, $sampleValues);
        $storeColumn = $this->detectStoreColumn($headerValues, $sampleValues, 0);
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

    private function detectStoreColumn(array $headerRow, array $sampleRows, int $default): int
    {
        $index = $this->detectColumnByName($headerRow, ['TIENDA', 'SUCURSAL', 'STORE', 'ALMACEN'], -1);
        if ($index !== -1) {
            return $index;
        }

        $counts = [];
        foreach ($sampleRows as $row) {
            foreach ($row as $idx => $value) {
                $trimmed = trim((string) $value);
                if ($trimmed === '') {
                    continue;
                }
                if (preg_match('/^\d{1,5}$/', $trimmed)) {
                    $counts[$idx] = isset($counts[$idx]) ? $counts[$idx] + 1 : 1;
                }
            }
        }

        if (!empty($counts)) {
            $maxIndex = array_search(max($counts), $counts, true);
            return (int) $maxIndex;
        }

        // fallback documentado
        return $default;
    }
}
