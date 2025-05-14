<?php

declare(strict_types=1);

namespace App\Infrastructure\Web;

class JsonRenderer
{
    /**
     * Render data as JSON
     * 
     * @param mixed $data
     * @param int $status
     * @return void
     */
    public function render($data, int $status = 200): void
    {
        // Set headers
        header('Content-Type: application/json');
        http_response_code($status);

        // Output JSON
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Render an error as JSON
     * 
     * @param string $message
     * @param int $status
     * @return void
     */
    public function renderError(string $message, int $status = 400): void
    {
        $this->render([
            'error' => true,
            'message' => $message
        ], $status);
    }
}
