<?php

declare(strict_types=1);

namespace App\Infrastructure\Excel;

interface ExcelReaderInterface
{
    /**
     * Read data from an Excel file
     * @param string $filePath Path to the Excel file
     * @return array Data from the Excel file
     */
    public function read(string $filePath): array;
}
