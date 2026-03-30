<?php
/**
 * Get Workforce Programs
 * Returns the list of workforce programs from JSON storage
 */

// Set umask to respect directory ACL defaults
umask(0000);

header('Content-Type: application/json');

// Path to data file
$data_file = __DIR__ . '/workforce-programs.json';

// Check if file exists
if (!file_exists($data_file)) {
    // Return empty array if file doesn't exist yet
    echo json_encode(['programs' => []]);
    exit;
}

// Read and return programs
$json_content = file_get_contents($data_file);
$programs = json_decode($json_content, true);

if ($programs === null) {
    // JSON decode error
    http_response_code(500);
    echo json_encode(['error' => 'Failed to read programs data']);
    exit;
}

echo json_encode(['programs' => $programs]);
?>
