<?php
declare(strict_types=1);

namespace Prosa\Orders\Application\Dto;

class StorePreviewDto
{
    /**
     * @var string
     */
    private $store;

    /**
     * @var OrderPreviewLineDto[]
     */
    private $lines;

    /**
     * @var float
     */
    private $totalClient;

    /**
     * @var float
     */
    private $totalSae;

    public function __construct(string $store, array $lines, float $totalClient, float $totalSae)
    {
        $this->store = $store;
        $this->lines = $lines;
        $this->totalClient = $totalClient;
        $this->totalSae = $totalSae;
    }

    public function store(): string
    {
        return $this->store;
    }

    /**
     * @return OrderPreviewLineDto[]
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function totalClient(): float
    {
        return $this->totalClient;
    }

    public function totalSae(): float
    {
        return $this->totalSae;
    }

    public function setLines(array $lines): void
    {
        $this->lines = $lines;
    }

    public function recalculateTotals(): void
    {
        $client = 0.0;
        $sae = 0.0;
        foreach ($this->lines as $line) {
            /** @var OrderPreviewLineDto $line */
            $client += $line->importeCliente();
            $sae += $line->importeSae();
        }
        $this->totalClient = $client;
        $this->totalSae = $sae;
    }
}
