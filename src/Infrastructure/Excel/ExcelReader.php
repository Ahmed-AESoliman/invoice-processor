<?php

declare(strict_types=1);

namespace App\Infrastructure\Excel;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

class ExcelReader implements ExcelReaderInterface
{
    private ?Spreadsheet $spreadsheet = null;
    private array $data = [];

    /**
     * Read data from an Excel file
     * @param string $filePath Path to the Excel file
     * @return array Data from the Excel file
     */
    public function read(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File does not exist: $filePath");
        }

        $this->spreadsheet = IOFactory::load($filePath);
        $activeSheet = $this->spreadsheet->getActiveSheet();

        // Get the highest row and column index
        $highestRow = $activeSheet->getHighestDataRow();
        $highestColumn = $activeSheet->getHighestDataColumn();
        // Get all data including the header row
        $data = [];

        // Get header row (row 1)
        $headerRow = $activeSheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0];
        // Get data rows (row 2 to highest row)
        for ($row = 2; $row <= $highestRow; $row++) {
            // Get the row data
            $rowData = $activeSheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, true)[0];
            // Create an associative array using header values as keys
            $rowAssoc = [];
            foreach ($headerRow as $index => $headerValue) {
                if (isset($rowData[$index])) {
                    $rowAssoc[$headerValue] = $rowData[$index];
                } else {
                    $rowAssoc[$headerValue] = null;
                }
            }

            $data[] = $rowAssoc;
        }

        $this->data = $data;

        return $data;
    }
}
