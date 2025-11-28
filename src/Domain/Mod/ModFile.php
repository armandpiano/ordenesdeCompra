<?php
declare(strict_types=1);

namespace Prosa\Orders\Domain\Mod;

class ModFile
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $contents;

    public function __construct(string $fileName, string $contents)
    {
        $this->fileName = $fileName;
        $this->contents = $contents;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getContents(): string
    {
        return $this->contents;
    }
}
