<?php

// Console entry point
require __DIR__ . '/../vendor/autoload.php';

use App\Commands\ImportCommand;
use App\Services\ImportService;
use App\Infrastructure\Database\SqliteConnection;
use App\Infrastructure\Excel\ExcelReader;

// Create dependencies
$connection = new SqliteConnection(__DIR__ . '/../var/database.sqlite');
$excelReader = new ExcelReader();
$importService = new ImportService($connection, $excelReader);

// Run command
$command = new ImportCommand($importService);
$exitCode = $command->run($argv);

exit($exitCode);
