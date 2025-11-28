<?php
declare(strict_types=1);

namespace Prosa\Orders\Application\UseCase\ImportExcelOrder;

class ImportExcelOrderCommand
{
    /**
     * @var string
     */
    private $clientCode;

    /**
     * @var string
     */
    private $excelFilePath;

    /**
     * @var string|null
     */
    private $storeKey;

    /**
     * @var string[]
     */
    private $storesPending;

    /**
     * @var string[]
     */
    private $storesCompleted;

    /**
     * @param string[] $storesPending
     * @param string[] $storesCompleted
     */
    public function __construct(string $clientCode, string $excelFilePath, $storeKey = null, array $storesPending = [], array $storesCompleted = [])
    {
        $this->clientCode = $clientCode;
        $this->excelFilePath = $excelFilePath;
        $this->storeKey = $storeKey !== null ? (string) $storeKey : null;
        $this->storesPending = $storesPending;
        $this->storesCompleted = $storesCompleted;
    }

    public function clientCode(): string
    {
        return $this->clientCode;
    }

    public function excelFilePath(): string
    {
        return $this->excelFilePath;
    }

    public function storeKey(): ?string
    {
        return $this->storeKey;
    }

    /**
     * @return string[]
     */
    public function storesPending(): array
    {
        return $this->storesPending;
    }

    /**
     * @return string[]
     */
    public function storesCompleted(): array
    {
        return $this->storesCompleted;
    }
}
