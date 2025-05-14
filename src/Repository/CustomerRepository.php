<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Infrastructure\Database\Connection;
use DateTime;

class CustomerRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Find a customer by ID
     * @param int $id
     * @return Customer|null
     */
    public function find(int $id): ?Customer
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, name, address, created_at, updated_at
            FROM customers
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
     * Find a customer by name
     * @param string $name
     * @return Customer|null
     */
    public function findByName(string $name): ?Customer
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, name, address, created_at, updated_at
            FROM customers
            WHERE name = :name
        ');
        $statement->execute(['name' => $name]);

        $data = $statement->fetch();
        if (!$data) {
            return null;
        }

        return $this->createEntityFromData($data);
    }

    /**
     * Find all customers
     * @return array
     */
    public function findAll(): array
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, name, address, created_at, updated_at
            FROM customers
        ');
        $statement->execute();

        $customers = [];
        while ($data = $statement->fetch()) {
            $customers[] = $this->createEntityFromData($data);
        }

        return $customers;
    }

    /**
     * Save a customer to the database
     * @param Customer $customer
     * @return Customer
     */
    public function save(Customer $customer): Customer
    {
        $now = new DateTime();

        if ($customer->getId() === null) {
            // Insert new customer
            $statement = $this->connection->connect()->prepare('
                INSERT INTO customers (name, address, created_at, updated_at)
                VALUES (:name, :address, :created_at, :updated_at)
            ');

            $statement->execute([
                'name' => $customer->getName(),
                'address' => $customer->getAddress(),
                'created_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s')
            ]);

            $customer->setId((int)$this->connection->connect()->lastInsertId());
            $customer->setCreatedAt($now);
            $customer->setUpdatedAt($now);
        } else {
            // Update existing customer
            $statement = $this->connection->connect()->prepare('
                UPDATE customers
                SET name = :name, address = :address, updated_at = :updated_at
                WHERE id = :id
            ');

            $statement->execute([
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'address' => $customer->getAddress(),
                'updated_at' => $now->format('Y-m-d H:i:s')
            ]);

            $customer->setUpdatedAt($now);
        }

        return $customer;
    }

    /**
     * Delete a customer from the database
     * @param Customer $customer
     * @return bool
     */
    public function delete(Customer $customer): bool
    {
        if ($customer->getId() === null) {
            return false;
        }

        $statement = $this->connection->connect()->prepare('
            DELETE FROM customers
            WHERE id = :id
        ');

        return $statement->execute([
            'id' => $customer->getId()
        ]);
    }

    /**
     * Create a Customer entity from database data
     * @param array $data
     * @return Customer
     */
    private function createEntityFromData(array $data): Customer
    {
        $customer = new Customer($data['name'], $data['address']);
        $customer->setId((int)$data['id']);

        if (isset($data['created_at'])) {
            $customer->setCreatedAt(new DateTime($data['created_at']));
        }

        if (isset($data['updated_at'])) {
            $customer->setUpdatedAt(new DateTime($data['updated_at']));
        }

        return $customer;
    }
}
