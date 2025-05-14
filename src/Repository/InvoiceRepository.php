<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Invoice;
use App\Infrastructure\Database\Connection;
use DateTime;

class InvoiceRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Find an invoice by ID
     * @param int $id
     * @return Invoice|null
     */
    public function find(int $id): ?Invoice
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, customer_id, invoice_date, grand_total, created_at, updated_at
            FROM invoices
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
     * Find invoices by customer ID
     * @param int $customerId
     * @return array
     */
    public function findByCustomerId(int $customerId): array
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, customer_id, invoice_date, grand_total, created_at, updated_at
            FROM invoices
            WHERE customer_id = :customer_id
        ');
        $statement->execute(['customer_id' => $customerId]);

        $invoices = [];
        while ($data = $statement->fetch()) {
            $invoices[] = $this->createEntityFromData($data);
        }

        return $invoices;
    }

    /**
     * Find all invoices
     * @return array
     */
    public function findAll(): array
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, customer_id, invoice_date, grand_total, created_at, updated_at
            FROM invoices
        ');
        $statement->execute();

        $invoices = [];
        while ($data = $statement->fetch()) {
            $invoices[] = $this->createEntityFromData($data);
        }

        return $invoices;
    }

    /**
     * Save an invoice to the database
     * @param Invoice $invoice
     * @return Invoice
     */
    public function save(Invoice $invoice): Invoice
    {
        $now = new DateTime();

        if ($invoice->getId() === null) {
            // Insert new invoice
            $statement = $this->connection->connect()->prepare('
                INSERT INTO invoices (customer_id, invoice_date, grand_total, created_at, updated_at)
                VALUES (:customer_id, :invoice_date, :grand_total, :created_at, :updated_at)
            ');

            $statement->execute([
                'customer_id' => $invoice->getCustomerId(),
                'invoice_date' => $invoice->getInvoiceDate()->format('Y-m-d H:i:s'),
                'grand_total' => $invoice->getGrandTotal(),
                'created_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s')
            ]);

            $invoice->setId((int)$this->connection->connect()->lastInsertId());
            $invoice->setCreatedAt($now);
            $invoice->setUpdatedAt($now);
        } else {
            // Update existing invoice
            $statement = $this->connection->connect()->prepare('
                UPDATE invoices
                SET customer_id = :customer_id, invoice_date = :invoice_date, 
                    grand_total = :grand_total, updated_at = :updated_at
                WHERE id = :id
            ');

            $statement->execute([
                'id' => $invoice->getId(),
                'customer_id' => $invoice->getCustomerId(),
                'invoice_date' => $invoice->getInvoiceDate()->format('Y-m-d H:i:s'),
                'grand_total' => $invoice->getGrandTotal(),
                'updated_at' => $now->format('Y-m-d H:i:s')
            ]);

            $invoice->setUpdatedAt($now);
        }

        return $invoice;
    }

    /**
     * Delete an invoice from the database
     * @param Invoice $invoice
     * @return bool
     */
    public function delete(Invoice $invoice): bool
    {
        if ($invoice->getId() === null) {
            return false;
        }

        $statement = $this->connection->connect()->prepare('
            DELETE FROM invoices
            WHERE id = :id
        ');

        return $statement->execute([
            'id' => $invoice->getId()
        ]);
    }

    /**
     * Create an Invoice entity from database data
     * @param array $data
     * @return Invoice
     */
    private function createEntityFromData(array $data): Invoice
    {
        $invoice = new Invoice(
            (int)$data['customer_id'],
            new DateTime($data['invoice_date']),
            (float)$data['grand_total']
        );

        $invoice->setId((int)$data['id']);

        if (isset($data['created_at'])) {
            $invoice->setCreatedAt(new DateTime($data['created_at']));
        }

        if (isset($data['updated_at'])) {
            $invoice->setUpdatedAt(new DateTime($data['updated_at']));
        }

        return $invoice;
    }
}
