<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;

class Invoice
{
    private ?int $id = null;
    private int $customerId;
    private DateTime $invoiceDate;
    private float $grandTotal;
    private array $items = [];
    private ?DateTime $createdAt = null;
    private ?DateTime $updatedAt = null;

    public function __construct(int $customerId, DateTime $invoiceDate, float $grandTotal)
    {
        $this->customerId = $customerId;
        $this->invoiceDate = $invoiceDate;
        $this->grandTotal = $grandTotal;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function setCustomerId(int $customerId): self
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getInvoiceDate(): DateTime
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(DateTime $invoiceDate): self
    {
        $this->invoiceDate = $invoiceDate;
        return $this;
    }

    public function getGrandTotal(): float
    {
        return $this->grandTotal;
    }

    public function setGrandTotal(float $grandTotal): self
    {
        $this->grandTotal = $grandTotal;
        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(InvoiceItem $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function setItems(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function toArray(): array
    {
        $itemsArray = [];
        foreach ($this->items as $item) {
            $itemsArray[] = $item->toArray();
        }

        return [
            'id' => $this->id,
            'customer_id' => $this->customerId,
            'invoice_date' => $this->invoiceDate->format('Y-m-d H:i:s'),
            'grand_total' => $this->grandTotal,
            'items' => $itemsArray,
            'created_at' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
        ];
    }
}
