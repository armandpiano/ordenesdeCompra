<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Catalog;

class CatalogArticle
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $description;

    /**
     * @var float
     */
    private $similarity;

    public function __construct(string $code, string $description, float $similarity = 100.0)
    {
        $this->code = $code;
        $this->description = $description;
        $this->similarity = $similarity;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function similarity(): float
    {
        return $this->similarity;
    }
}
