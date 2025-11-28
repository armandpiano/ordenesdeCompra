<?php
declare(strict_types=1);

namespace Prosa\Orders\Application\Dto;

class OrderPreviewLineDto
{
    /**
     * @var string
     */
    private $store;

    /**
     * @var string|int
     */
    private $index;

    /**
     * @var string|null
     */
    private $productCode;

    /**
     * @var string
     */
    private $description;

    /**
     * @var float
     */
    private $quantity;

    /**
     * @var float
     */
    private $priceCliente;

    /**
     * @var float
     */
    private $priceSae;

    /**
     * @var float
     */
    private $importeCliente;

    /**
     * @var float
     */
    private $importeSae;

    /**
     * @var bool
     */
    private $productFound;

    /**
     * @var string
     */
    private $matchType;

    public function __construct(
        string $store,
        $index,
        ?string $productCode,
        string $description,
        float $quantity,
        float $priceCliente,
        float $priceSae,
        float $importeCliente,
        float $importeSae,
        bool $productFound,
        string $matchType
    ) {
        $this->store = $store;
        $this->index = $index;
        $this->productCode = $productCode;
        $this->description = $description;
        $this->quantity = $quantity;
        $this->priceCliente = $priceCliente;
        $this->priceSae = $priceSae;
        $this->importeCliente = $importeCliente;
        $this->importeSae = $importeSae;
        $this->productFound = $productFound;
        $this->matchType = $matchType;
    }

    public function store(): string
    {
        return $this->store;
    }

    public function index()
    {
        return $this->index;
    }

    public function productCode(): ?string
    {
        return $this->productCode;
    }

    public function setProductCode(?string $productCode): void
    {
        $this->productCode = $productCode;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    public function priceCliente(): float
    {
        return $this->priceCliente;
    }

    public function priceSae(): float
    {
        return $this->priceSae;
    }

    public function setPriceSae(float $price): void
    {
        $this->priceSae = $price;
    }

    public function importeCliente(): float
    {
        return $this->importeCliente;
    }

    public function importeSae(): float
    {
        return $this->importeSae;
    }

    public function setImporteSae(float $importe): void
    {
        $this->importeSae = $importe;
    }

    public function productFound(): bool
    {
        return $this->productFound;
    }

    public function setProductFound(bool $productFound): void
    {
        $this->productFound = $productFound;
    }

    public function matchType(): string
    {
        return $this->matchType;
    }

    public function setMatchType(string $matchType): void
    {
        $this->matchType = $matchType;
    }
}
