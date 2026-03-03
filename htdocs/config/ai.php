<?php

class AIService {
    private string $baseUrl;
    private array $defaultHeaders;
    private int $timeout;

    /**
     * Constructor - Set up the client with base configuration
     * 
     * @param int    $timeout    - Max seconds to wait for a response
     * @param array  $headers    - Default headers sent with every request
     */
    public function __construct(int $timeout = 10, array $headers = []) {
        $this->baseUrl = getenv("AI_SERVICE_URL") ?: "http://ai:5000";
        $this->timeout = $timeout;
        $this->defaultHeaders = array_merge([
            "Content-Type: application/json",
            "Accept: application/json"
        ], $headers);
    }

    /**
     * Core request method - sends POST request to AI service
     * 
     * @param string $endpoint - API path (e.g. "/health", "/generate")
     * @param array  $data     - Data to send as JSON body
     * @return ?array          - Decoded JSON response or null on failure
     */
    private function request(string $endpoint, array $data): ?array {
        $ch = curl_init();
        $url = $this->baseUrl . $endpoint;

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $this->defaultHeaders,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
        ];
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        // Check for network errors (DNS failure, timeout, etc.)
        if (curl_errno($ch)) {
            throw new Exception("cURL Error: " . curl_error($ch));
        }

        // Check HTTP status (400+ means something went wrong)
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error: {$httpCode} - {$url}");
        }

        // Decode JSON response into array
        $decoded = json_decode($response, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Error: " . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Check if AI service is running
     * 
     * @return ?array - Service status (e.g. ["status" => "ok"])
     */
    public function health(): ?array {
        return $this->request("/health", []);
    }

    /**
     * Send a prompt to AI and get form structure back
     * 
     * @param string $prompt - Natural language form description
     * @return ?array        - Generated form schema as array
     */
    public function generate(string $prompt): ?array {
        return $this->request("/generate", ["prompt" => $prompt]);
    }   
}