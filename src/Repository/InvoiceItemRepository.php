<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\InvoiceItem;
use App\Infrastructure\Database\Connection;
use DateTime;

class InvoiceItemRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Find an invoice item by ID
     * 
     * @param int $id
     * @return InvoiceItem|null
     */
    public function find(int $id): ?InvoiceItem
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, invoice_id, product_id, quantity, price, total, created_at, updated_at
            FROM invoice_items
            WHERE id = :id
        ');
        $statement->execute(['id' => $id]);

        $data = $statement->fetch();
        if (!$data) {
            return null;
        }

        return $this->createEntityFromData($data);
    }

    /**
     * Find invoice items by invoice ID
     * 
     * @param int $invoiceId
     * @return array
     */
    public function findByInvoiceId(int $invoiceId): array
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, invoice_id, product_id, quantity, price, total, created_at, updated_at
            FROM invoice_items
            WHERE invoice_id = :invoice_id
        ');
        $statement->execute(['invoice_id' => $invoiceId]);

        $items = [];
        while ($data = $statement->fetch()) {
            $items[] = $this->createEntityFromData($data);
        }

        return $items;
    }

    /**
     * Save an invoice item to the database
     * 
     * @param InvoiceItem $item
     * @return InvoiceItem
     */
    public function save(InvoiceItem $item): InvoiceItem
    {
        $now = new DateTime();

        if ($item->getId() === null) {
            // Insert new invoice item
            $statement = $this->connection->connect()->prepare('
                INSERT INTO invoice_items (invoice_id, product_id, quantity, price, total, created_at, updated_at)
                VALUES (:invoice_id, :product_id, :quantity, :price, :total, :created_at, :updated_at)
            ');

            $statement->execute([
                'invoice_id' => $item->getInvoiceId(),
                'product_id' => $item->getProductId(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'total' => $item->getTotal(),
                'created_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s')
            ]);

            $item->setId((int)$this->connection->connect()->lastInsertId());
            $item->setCreatedAt($now);
            $item->setUpdatedAt($now);
        } else {
            // Update existing invoice item
            $statement = $this->connection->connect()->prepare('
                UPDATE invoice_items
                SET invoice_id = :invoice_id, product_id = :product_id,
                    quantity = :quantity, price = :price, total = :total,
                    updated_at = :updated_at
                WHERE id = :id
            ');

            $statement->execute([
                'id' => $item->getId(),
                'invoice_id' => $item->getInvoiceId(),
                'product_id' => $item->getProductId(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'total' => $item->getTotal(),
                'updated_at' => $now->format('Y-m-d H:i:s')
            ]);

            $item->setUpdatedAt($now);
        }

        return $item;
    }

    /**
     * Delete an invoice item from the database
     * 
     * @param InvoiceItem $item
     * @return bool
     */
    public function delete(InvoiceItem $item): bool
    {
        if ($item->getId() === null) {
            return false;
        }

        $statement = $this->connection->connect()->prepare('
            DELETE FROM invoice_items
            WHERE id = :id
        ');

        return $statement->execute([
            'id' => $item->getId()
        ]);
    }

    /**
     * Delete all invoice items for an invoice
     * 
     * @param int $invoiceId
     * @return bool
     */
    public function deleteByInvoiceId(int $invoiceId): bool
    {
        $statement = $this->connection->connect()->prepare('
            DELETE FROM invoice_items
            WHERE invoice_id = :invoice_id
        ');

        return $statement->execute([
            'invoice_id' => $invoiceId
        ]);
    }

    /**
     * Create an InvoiceItem entity from database data
     * 
     * @param array $data
     * @return InvoiceItem
     */
    private function createEntityFromData(array $data): InvoiceItem
    {
        $item = new InvoiceItem(
            (int)$data['invoice_id'],
            (int)$data['product_id'],
            (int)$data['quantity'],
            (float)$data['price'],
            (float)$data['total']
        );

        $item->setId((int)$data['id']);

        if (isset($data['created_at'])) {
            $item->setCreatedAt(new DateTime($data['created_at']));
        }

        if (isset($data['updated_at'])) {
            $item->setUpdatedAt(new DateTime($data['updated_at']));
        }

        return $item;
    }
}