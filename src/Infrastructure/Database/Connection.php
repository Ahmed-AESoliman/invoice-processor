<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

interface Connection
{
    /**
     * Create a connection to the database
     * 
     * @return PDO
     */
    public function connect(): PDO;

    /**
     * Close the database connection
     * 
     * @return void
     */
    public function disconnect(): void;

    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool;

    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit(): bool;

    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollback(): bool;

    /**
     * Check if tables exist and create them if needed
     * 
     * @return void
     */
    public function initSchema(): void;
}
