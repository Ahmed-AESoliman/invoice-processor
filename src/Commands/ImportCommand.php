<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\ImportService;

class ImportCommand
{
    private ImportService $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Run the import command
     * 
     * @param array $args Command line arguments
     * @return int Exit code
     */
    public function run(array $args): int
    {
        // Check for help command
        if (isset($args[1]) && in_array($args[1], ['-h', '--help'])) {
            $this->showHelp();
            return 0;
        }

        // Check for file argument
        if (!isset($args[1])) {
            echo "Error: Missing file path argument.\n";
            $this->showHelp();
            return 1;
        }

        $filePath = $args[1];

        // Check if file exists
        if (!file_exists($filePath)) {
            echo "Error: File does not exist: {$filePath}\n";
            return 1;
        }

        try {
            // Import data
            $result = $this->importService->importFromExcel($filePath);

            echo "Import completed successfully.\n";
            echo "Processed {$result['invoice_count']} invoices with {$result['item_count']} items.\n";

            return 0;
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Show command help
     * 
     * @return void
     */
    private function showHelp(): void
    {
        echo "Usage: php bin/console.php import <file_path>\n";
        echo "\n";
        echo "Arguments:\n";
        echo "  file_path                Path to the Excel file to import\n";
        echo "\n";
        echo "Options:\n";
        echo "  -h, --help               Display this help message\n";
    }
}
