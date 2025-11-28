<?php
declare(strict_types=1);

namespace Prosa\Orders\Infrastructure\Catalog;

use Prosa\Orders\Domain\Catalog\CatalogArticle;
use Prosa\Orders\Domain\Catalog\CatalogRepository;

class TxtCatalogRepository implements CatalogRepository
{
    /**
     * @var array<string, string>
     */
    private static $articlesByCode = [];

    /**
     * @var array<string, string>
     */
    private static $articlesByDescription = [];

    /**
     * @var string
     */
    private $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->load();
    }

    private function load(): void
    {
        if (!empty(self::$articlesByCode)) {
            return;
        }

        if (!file_exists($this->filePath)) {
            throw new \RuntimeException('No se encontró el archivo de catálogo: ' . $this->filePath);
        }

        $handle = fopen($this->filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el catálogo de artículos.');
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            // Suponemos formato delimitado por tabulador: codigo<TAB>descripcion
            $parts = explode("\t", $line, 2);
            if (count($parts) < 2) {
                continue;
            }
            $code = trim($parts[0]);
            $description = trim($parts[1]);
            self::$articlesByCode[$code] = $description;
            $normalized = $this->normalizeDescription($description);
            self::$articlesByDescription[$normalized] = $code;
        }

        fclose($handle);
    }

    public function findByCode(string $code): ?CatalogArticle
    {
        if (isset(self::$articlesByCode[$code])) {
            return new CatalogArticle($code, self::$articlesByCode[$code]);
        }
        return null;
    }

    public function findBestMatchByDescription(string $description, float $minSimilarity): ?CatalogArticle
    {
        $normalizedSearch = $this->normalizeDescription($description);
        $best = null;
        $bestSimilarity = 0.0;
        foreach (self::$articlesByDescription as $normalized => $code) {
            similar_text($normalizedSearch, $normalized, $percent);
            if ($percent >= $minSimilarity && $percent > $bestSimilarity) {
                $bestSimilarity = $percent;
                $best = $code;
            }
        }

        if ($best === null) {
            return null;
        }

        return new CatalogArticle($best, self::$articlesByCode[$best], $bestSimilarity);
    }

    public function detectArticleColumn(array $headerRow, array $sampleRows): int
    {
        $maxMatches = -1;
        $bestColumn = 0;
        $columnCount = count($headerRow);
        for ($i = 0; $i < $columnCount; $i++) {
            $matches = 0;
            foreach ($sampleRows as $row) {
                if (!isset($row[$i])) {
                    continue;
                }
                $value = trim((string) $row[$i]);
                if (preg_match('/^\d{5}$/', $value) && isset(self::$articlesByCode[$value])) {
                    $matches++;
                }
            }
            if ($matches > $maxMatches) {
                $maxMatches = $matches;
                $bestColumn = $i;
            }
        }

        return $bestColumn;
    }

    private function normalizeDescription(string $description): string
    {
        $normalized = strtoupper($description);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }
}
