<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use App\Infrastructure\Database\Connection;
use DateTime;

class ProductRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Find a product by ID
     * 
     * @param int $id
     * @return Product|null
     */
    public function find(int $id): ?Product
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, name, price, created_at, updated_at
            FROM products
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
     * Find a product by name
     * 
     * @param string $name
     * @return Product|null
     */
    public function findByName(string $name): ?Product
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, name, price, created_at, updated_at
            FROM products
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
     * Find all products
     * 
     * @return array
     */
    public function findAll(): array
    {
        $statement = $this->connection->connect()->prepare('
            SELECT id, name, price, created_at, updated_at
            FROM products
        ');
        $statement->execute();

        $products = [];
        while ($data = $statement->fetch()) {
            $products[] = $this->createEntityFromData($data);
        }

        return $products;
    }

    /**
     * Save a product to the database
     * 
     * @param Product $product
     * @return Product
     */
    public function save(Product $product): Product
    {
        $now = new DateTime();

        if ($product->getId() === null) {
            // Insert new product
            $statement = $this->connection->connect()->prepare('
                INSERT INTO products (name, price, created_at, updated_at)
                VALUES (:name, :price, :created_at, :updated_at)
            ');

            $statement->execute([
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'created_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s')
            ]);

            $product->setId((int)$this->connection->connect()->lastInsertId());
            $product->setCreatedAt($now);
            $product->setUpdatedAt($now);
        } else {
            // Update existing product
            $statement = $this->connection->connect()->prepare('
                UPDATE products
                SET name = :name, price = :price, updated_at = :updated_at
                WHERE id = :id
            ');

            $statement->execute([
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'updated_at' => $now->format('Y-m-d H:i:s')
            ]);

            $product->setUpdatedAt($now);
        }

        return $product;
    }

    /**
     * Delete a product from the database
     * 
     * @param Product $product
     * @return bool
     */
    public function delete(Product $product): bool
    {
        if ($product->getId() === null) {
            return false;
        }

        $statement = $this->connection->connect()->prepare('
            DELETE FROM products
            WHERE id = :id
        ');

        return $statement->execute([
            'id' => $product->getId()
        ]);
    }

    /**
     * Create a Product entity from database data
     * 
     * @param array $data
     * @return Product
     */
    private function createEntityFromData(array $data): Product
    {
        $product = new Product($data['name'], (float)$data['price']);
        $product->setId((int)$data['id']);

        if (isset($data['created_at'])) {
            $product->setCreatedAt(new DateTime($data['created_at']));
        }

        if (isset($data['updated_at'])) {
            $product->setUpdatedAt(new DateTime($data['updated_at']));
        }

        return $product;
    }
}
