<?php

class AIService {
    private string $baseUrl;

    // Initalize the service
    public function __construct() {
        $this->baseUrl = getenv("AI_SERVICE_URL") ?: "http://ai:5000";
    }

    // TODO: request to AI service to generate a response based on the input
    private function request(string $endpoint, array $data): ?array {
        $ch = curl_init();
        return [];
    }



    
}