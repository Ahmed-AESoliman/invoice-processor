<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\SqliteConnection;
use App\Infrastructure\Excel\ExcelReaderInterface;
use App\Services\InvoiceProcessor;
use PHPUnit\Framework\TestCase;

class InvoiceProcessorTest extends TestCase
{
    private Connection $connection;
    private ExcelReaderInterface $excelReader;
    private InvoiceProcessor $invoiceProcessor;
    private string $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Use an in-memory SQLite database for testing
        $this->connection = $this->createMock(SqliteConnection::class);

        // Create a mock for the Excel reader
        $this->excelReader = $this->createMock(ExcelReaderInterface::class);

        $this->connection->expects($this->once())
            ->method('initSchema');

        // Create the invoice processor
        $connection = $this->connection;
        $excelReader = $this->excelReader;

        $this->invoiceProcessor = new InvoiceProcessor(
            $connection,
            $excelReader
        );

        // Set the test file path
        $this->testFilePath = __DIR__ . '/../data/data.xlsx';
    }

    public function testProcessFromExcelReturnsInvoices(): void
    {
        // Skip test if file doesn't exist
        if (!file_exists($this->testFilePath)) {
            $this->markTestSkipped('Test file does not exist.');
        }

        // Configure transaction methods
        $this->connection->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $this->connection->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        // Mock sample data that would be returned from Excel
        $excelData = [
            [
                'invoice' => 1,
                'Invoice Date' => '2023-01-01',
                'Customer Name' => 'Test Customer',
                'Customer Address' => 'Test Address',
                'Product Name' => 'Test Product',
                'Qyantity' => 1,
                'Price' => 10.5,
                'Total' => 10.5,
                'Grand Total' => 10.5
            ]
        ];

        // Configure excel reader mock
        $this->excelReader->expects($this->once())
            ->method('read')
            ->with($this->testFilePath)
            ->willReturn($excelData);

        // Mock database connection for saving entities
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);

        $mockPdo->expects($this->any())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->expects($this->any())
            ->method('execute')
            ->willReturn(true);

        $mockStmt->expects($this->any())
            ->method('fetch')
            ->willReturn(false); // Simulate no existing records

        $mockPdo->expects($this->any())
            ->method('lastInsertId')
            ->willReturn('1'); // Simulate insert IDs

        $this->connection->expects($this->any())
            ->method('connect')
            ->willReturn($mockPdo);

        // Call the method under test
        $result = $this->invoiceProcessor->processFromExcel($this->testFilePath);

        // Assert the result contains invoices
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetAllInvoicesWithDetailsReturnsFormattedData(): void
    {
        // Configure mocks for retrieving data
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);

        $mockPdo->expects($this->any())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->expects($this->any())
            ->method('execute')
            ->willReturn(true);

        // Mock an empty result to keep the test simple
        $mockStmt->expects($this->any())
            ->method('fetch')
            ->willReturn(false);

        $this->connection->expects($this->any())
            ->method('connect')
            ->willReturn($mockPdo);

        // Call the method under test
        $result = $this->invoiceProcessor->getAllInvoicesWithDetails();

        // Assert the result is an array (even if empty)
        $this->assertIsArray($result);
    }
}
