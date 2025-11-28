<?php
declare(strict_types=1);

namespace Prosa\Orders\Application\Dto;

use Prosa\Orders\Domain\Client\Client;

class OrderPreviewDto
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var StorePreviewDto[]
     */
    private $stores;

    /**
     * @var string|null
     */
    private $selectedStore;

    /**
     * @var string[]
     */
    private $storesPending;

    /**
     * @var string[]
     */
    private $storesCompleted;

    public function __construct(Client $client, array $stores, array $storesPending, array $storesCompleted = [], $selectedStore = null)
    {
        $this->client = $client;
        $this->stores = $stores;
        $this->storesPending = $storesPending;
        $this->storesCompleted = $storesCompleted;
        $this->selectedStore = $selectedStore;
    }

    public function client(): Client
    {
        return $this->client;
    }

    /**
     * @return StorePreviewDto[]
     */
    public function stores(): array
    {
        return $this->stores;
    }

    public function selectedStore(): ?string
    {
        return $this->selectedStore;
    }

    public function selectStore(string $store): void
    {
        $this->selectedStore = $store;
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

    public function markStoreCompleted(string $store): void
    {
        $this->storesPending = array_values(array_filter($this->storesPending, function ($pending) use ($store) {
            return $pending !== $store;
        }));
        if (!in_array($store, $this->storesCompleted, true)) {
            $this->storesCompleted[] = $store;
        }
    }
}
