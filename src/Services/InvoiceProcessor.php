<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\Product;
use App\Repository\CustomerRepository;
use App\Repository\InvoiceRepository;
use App\Repository\InvoiceItemRepository;
use App\Repository\ProductRepository;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Excel\ExcelReaderInterface;
use DateTime;

class InvoiceProcessor
{
    private Connection $connection;
    private ExcelReaderInterface $excelReader;
    private CustomerRepository $customerRepository;
    private ProductRepository $productRepository;
    private InvoiceRepository $invoiceRepository;
    private InvoiceItemRepository $invoiceItemRepository;

    /**
     * Constructor
     * @param Connection $connection
     * @param ExcelReaderInterface $excelReader
     */
    public function __construct(
        Connection $connection,
        ExcelReaderInterface $excelReader
    ) {
        $this->connection = $connection;
        $this->excelReader = $excelReader;

        // Initialize repositories
        $this->customerRepository = new CustomerRepository($connection);
        $this->productRepository = new ProductRepository($connection);
        $this->invoiceRepository = new InvoiceRepository($connection);
        $this->invoiceItemRepository = new InvoiceItemRepository($connection);

        // Initialize database schema if needed
        $this->connection->initSchema();
    }

    /**
     * Process invoices from an Excel file
     * @param string $filePath
     * @return array Processed invoices
     */
    public function processFromExcel(string $filePath): array
    {
        // Read data from Excel
        $data = $this->excelReader->read($filePath);
        // Group data by invoice number
        $invoiceGroups = [];
        foreach ($data as $row) {
            $invoiceNumber = $row['invoice'];
            if (!isset($invoiceGroups[$invoiceNumber])) {
                $invoiceGroups[$invoiceNumber] = [];
            }
            $invoiceGroups[$invoiceNumber][] = $row;
        }

        // Process each invoice group
        $processedInvoices = [];

        $this->connection->beginTransaction();

        try {
            foreach ($invoiceGroups as $invoiceRows) {
                // Process invoice
                $invoice = $this->processInvoice($invoiceRows);
                $processedInvoices[] = $invoice;
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }

        return $processedInvoices;
    }

    /**
     * Process a single invoice
     * @param array $invoiceRows
     * @return Invoice
     */
    private function processInvoice(array $invoiceRows): Invoice
    {
        // Get the first row for customer and invoice data
        $firstRow = $invoiceRows[0];
        // Process customer
        $customer = $this->processCustomer($firstRow['Customer Name'], $firstRow['Customer Address']);

        // Create invoice
        $invoiceDate = new DateTime($firstRow['Invoice Date']);
        $grandTotal = (float)$firstRow['Grand Total'];

        $invoice = new Invoice($customer->getId(), $invoiceDate, $grandTotal);
        $invoice = $this->invoiceRepository->save($invoice);

        // Process invoice items
        foreach ($invoiceRows as $row) {
            $this->processInvoiceItem($invoice, $row);
        }

        return $invoice;
    }

    /**
     * Process a customer
     * @param string $name
     * @param string $address
     * @return Customer
     */
    private function processCustomer(string $name, string $address): Customer
    {
        // Check if customer already exists
        $customer = $this->customerRepository->findByName($name);

        if ($customer === null) {
            // Create new customer
            $customer = new Customer($name, $address);
            $customer = $this->customerRepository->save($customer);
        }

        return $customer;
    }

    /**
     * Process a product
     * @param string $name
     * @param float $price
     * @return Product
     */
    private function processProduct(string $name, float $price): Product
    {
        // Check if product already exists
        $product = $this->productRepository->findByName($name);

        if ($product === null) {
            // Create new product
            $product = new Product($name, $price);
            $product = $this->productRepository->save($product);
        }

        return $product;
    }

    /**
     * Process an invoice item
     * @param Invoice $invoice
     * @param array $row
     * @return InvoiceItem
     */
    private function processInvoiceItem(Invoice $invoice, array $row): InvoiceItem
    {
        // Process product
        $product = $this->processProduct($row['Product Name'], (float)$row['Price']);

        // Create invoice item
        $invoiceItem = new InvoiceItem(
            $invoice->getId(),
            $product->getId(),
            (int)$row['Qyantity'],
            (float)$row['Price'],
            (float)$row['Total']
        );

        $invoiceItem = $this->invoiceItemRepository->save($invoiceItem);
        $invoice->addItem($invoiceItem);

        return $invoiceItem;
    }

    /**
     * Get all invoices with related data
     * @return array
     */
    public function getAllInvoicesWithDetails(): array
    {
        $invoices = $this->invoiceRepository->findAll();
        $result = [];

        foreach ($invoices as $invoice) {
            $customer = $this->customerRepository->find($invoice->getCustomerId());

            // Get invoice items
            $items = $this->invoiceItemRepository->findByInvoiceId($invoice->getId());
            $invoice->setItems($items);

            // Add product details to invoice items
            $itemsWithProducts = [];
            foreach ($items as $item) {
                $product = $this->productRepository->find($item->getProductId());

                $itemsWithProducts[] = [
                    'id' => $item->getId(),
                    'product_name' => $product ? $product->getName() : 'Unknown Product',
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getPrice(),
                    'total' => $item->getTotal()
                ];
            }

            $result[] = [
                'id' => $invoice->getId(),
                'invoice_date' => $invoice->getInvoiceDate()->format('Y-m-d H:i:s'),
                'customer' => [
                    'id' => $customer ? $customer->getId() : null,
                    'name' => $customer ? $customer->getName() : 'Unknown Customer',
                    'address' => $customer ? $customer->getAddress() : ''
                ],
                'items' => $itemsWithProducts,
                'grand_total' => $invoice->getGrandTotal()
            ];
        }

        return $result;
    }
}
