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

        $lines = [];
        $currentStore = null;
        $headerIndices = null;

        foreach ($rows as $row) {
            if ($this->isStoreHeaderRow($row)) {
                $currentStore = trim((string) $row['B']);
                $headerIndices = null;
                continue;
            }

            if ($currentStore === null) {
                continue;
            }

            if ($headerIndices === null) {
                if ($this->rowContainsHeaders($row)) {
                    $headerIndices = $this->detectHeaderIndices($row);
                }
                continue;
            }

            if ($this->isRowEmpty($row)) {
                $currentStore = null;
                $headerIndices = null;
                continue;
            }

            if ($this->isEndOfStoreSection($row)) {
                $currentStore = null;
                $headerIndices = null;
                continue;
            }

            $quantity = $this->toFloat($this->getValue($row, $headerIndices['quantity']));
            $providerKey = (string) $this->getValue($row, $headerIndices['provider']);
            $description = (string) $this->getValue($row, $headerIndices['description']);
            $unit = (string) $this->getValue($row, $headerIndices['unit']);
            $priceClient = $this->toFloat($this->getValue($row, $headerIndices['cost']));
            $total = $this->toFloat($this->getValue($row, $headerIndices['total']));

            $lines[] = [
                'store'       => $currentStore,
                'rawArticle'  => $description,
                'quantity'    => $quantity,
                'priceClient' => $priceClient,
                'providerKey' => $providerKey,
                'unit'        => $unit,
                'total'       => $total,
            ];
        }

        return $lines;
    }

    private function isStoreHeaderRow(array $row): bool
    {
        $first = isset($row['A']) ? trim((string) $row['A']) : '';
        return strtoupper($first) === 'TIENDA:' && trim((string) $row['B']) !== '';
    }

    private function rowContainsHeaders(array $row): bool
    {
        foreach ($row as $value) {
            $text = strtoupper(trim((string) $value));
            if ($text === '') {
                continue;
            }
            if (strpos($text, 'CANTIDAD') !== false || strpos($text, 'CLAVE') !== false || strpos($text, 'DESCRIP') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function detectHeaderIndices(array $row): array
    {
        $indices = [
            'quantity' => 'A',
            'provider' => 'B',
            'description' => 'C',
            'unit' => 'D',
            'cost' => 'E',
            'total' => 'F',
        ];

        foreach ($row as $column => $value) {
            $text = strtoupper(trim((string) $value));
            if (strpos($text, 'CANT') !== false) {
                $indices['quantity'] = $column;
            }
            if (strpos($text, 'CLAVE') !== false) {
                $indices['provider'] = $column;
            }
            if (strpos($text, 'DESCRIP') !== false) {
                $indices['description'] = $column;
            }
            if (strpos($text, 'UNID') !== false) {
                $indices['unit'] = $column;
            }
            if (strpos($text, 'COSTO') !== false || strpos($text, 'PRECIO') !== false) {
                $indices['cost'] = $column;
            }
            if (strpos($text, 'TOTAL') !== false) {
                $indices['total'] = $column;
            }
        }

        return $indices;
    }

    private function isEndOfStoreSection(array $row): bool
    {
        foreach ($row as $value) {
            $text = strtoupper(trim((string) $value));
            if ($text === '') {
                continue;
            }
            if (strpos($text, 'TOTAL') === 0) {
                return true;
            }
        }

        return false;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $row
     * @param string $index
     * @return mixed|null
     */
    private function getValue(array $row, $index)
    {
        return isset($row[$index]) ? $row[$index] : null;
    }

    private function toFloat($value): float
    {
        $normalized = (string) $value;
        $normalized = str_replace(',', '', $normalized);
        return (float) $normalized;
    }
}
