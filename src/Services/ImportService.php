<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\InvoiceProcessor;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Excel\ExcelReaderInterface;

class ImportService
{

    private InvoiceProcessor $invoiceProcessor;


    public function __construct(
        Connection $connection,
        ExcelReaderInterface $excelReader
    ) {

        $this->invoiceProcessor = new InvoiceProcessor($connection, $excelReader);
    }

    /**
     * Import data from an Excel file
     * @param string $filePath
     * @return array Import statistics
     */
    public function importFromExcel(string $filePath): array
    {
        // Process invoices
        $invoices = $this->invoiceProcessor->processFromExcel($filePath);

        // Count items
        $itemCount = 0;
        foreach ($invoices as $invoice) {
            $itemCount += count($invoice->getItems());
        }

        return [
            'invoice_count' => count($invoices),
            'item_count' => $itemCount,

        ];
    }
}
