<?php

// Web entry point

use App\Infrastructure\Database\SqliteConnection;
use App\Infrastructure\Excel\ExcelReader;
use App\Infrastructure\Web\JsonRenderer;
use App\Services\InvoiceProcessor;

require __DIR__ . '/../vendor/autoload.php';



// Create dependencies
$connection = new SqliteConnection(__DIR__ . '/../var/database.sqlite');
$excelReader = new ExcelReader();

// Initialize processor
$invoiceProcessor = new InvoiceProcessor($connection, $excelReader);

// Create renderer
$renderer = new JsonRenderer();

// Get all invoices with details
try {
    $invoices = $invoiceProcessor->getAllInvoicesWithDetails();
    $renderer->render([
        'success' => true,
        'count' => count($invoices),
        'invoices' => $invoices
    ]);
} catch (\Exception $e) {
    $renderer->renderError($e->getMessage(), 500);
}
