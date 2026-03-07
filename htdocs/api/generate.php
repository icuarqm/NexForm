<?php

// This endpoint receives a prompt, sends it to AI, and saves the generated form
header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../config/ai.php';

// Must be logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $user = currentUser();

    // Get and validate prompt
    $prompt = isset($_POST['prompt']) ? trim($_POST['prompt']) : '';

    if (empty($prompt)) {
        http_response_code(400);
        echo json_encode(['error' => 'prompt is required']);
        exit();
    }

    try {
        // Send prompt to AI service and get form structure
        $ai = new AIService();
        $result = $ai->generate($prompt);

        // Generate unique share token
        $shareToken = bin2hex(random_bytes(8));

        // Save generated form to database
        $db = new Database();
        $pdo = $db->connect();
        $stmt = $pdo->prepare("INSERT INTO forms (user_id, title, description, schema_json, prompt_used, share_token) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user['id'],
            $result['title'] ?? 'Untitled Form',
            $result['description'] ?? '',
            json_encode($result),
            $prompt,
            $shareToken
        ]);

        // Return form data with its new database ID
        $formId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'form_id' => $formId, 'form' => $result]);
        exit();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Form generation failed: ' . $e->getMessage()]);
        exit();
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit();
}