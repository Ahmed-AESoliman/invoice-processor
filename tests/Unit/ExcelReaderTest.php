<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Excel\ExcelReader;
use PHPUnit\Framework\TestCase;

class ExcelReaderTest extends TestCase
{
    private string $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFilePath = __DIR__ . '/../data/data.xlsx';
    }

    public function testExcelReaderCanReadFile(): void
    {
        // Skip test if file doesn't exist
        if (!file_exists($this->testFilePath)) {
            $this->markTestSkipped('Test file does not exist.');
        }

        $reader = new ExcelReader();
        $data = $reader->read($this->testFilePath);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function testExcelReaderThrowsExceptionForNonExistentFile(): void
    {
        $reader = new ExcelReader();

        $this->expectException(\InvalidArgumentException::class);
        $reader->read('non-existent-file.xlsx');
    }
}
