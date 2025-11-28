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

    public function __construct(string $clientCode, string $excelFilePath)
    {
        $this->clientCode = $clientCode;
        $this->excelFilePath = $excelFilePath;
    }

    public function clientCode(): string
    {
        return $this->clientCode;
    }

    public function excelFilePath(): string
    {
        return $this->excelFilePath;
    }
}
