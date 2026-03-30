<?php
/**
 * Delete Workforce Program
 * Removes a program from the JSON storage
 */

// Set umask to respect directory ACL defaults
umask(0000);

header('Content-Type: application/json');

// Get program ID
$id = $_POST['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Program ID is required']);
    exit;
}

// Path to data file
$data_file = __DIR__ . '/workforce-programs.json';

// Check if file exists
if (!file_exists($data_file)) {
    echo json_encode(['success' => false, 'message' => 'No programs found']);
    exit;
}

// Load existing programs
$json_content = file_get_contents($data_file);
$programs = json_decode($json_content, true);

if ($programs === null) {
    echo json_encode(['success' => false, 'message' => 'Failed to read programs data']);
    exit;
}

// Find and remove program
$found = false;
$filtered_programs = [];
foreach ($programs as $program) {
    if ($program['id'] === $id) {
        $found = true;
        // Don't add to filtered array (effectively deleting it)
    } else {
        $filtered_programs[] = $program;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Program not found']);
    exit;
}

// Save updated list
$json_content = json_encode($filtered_programs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (file_put_contents($data_file, $json_content) === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to save changes']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Program deleted successfully']);
?>
