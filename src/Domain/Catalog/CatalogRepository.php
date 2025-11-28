<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Catalog;

interface CatalogRepository
{
    /**
     * @return CatalogArticle|null
     */
    public function findByCode(string $code);

    /**
     * @return CatalogArticle|null
     */
    public function findBestMatchByDescription(string $description, float $minSimilarity);

    public function detectArticleColumn(array $headerRow, array $sampleRows): int;
}
