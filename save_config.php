<?php
// save_config.php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method Not Allowed");
}

require_once __DIR__ . '/config.php';

if (!file_exists($configFile)) {
    http_response_code(500);
    exit("Config file not found.");
}

$lines = file($configFile);
$newContent = "";
$updatedKeys = [];

// Get POST data (excluding hidden standard fields if any)
$postData = $_POST;

foreach ($lines as $line) {
    $trimmedLine = trim($line);
    
    // Check if line is an active config `key = value`
    // Regex matches: start, (capture key), space*, =, space*, (capture value)
    if (preg_match('/^([^#;][^=]*?)\s*=\s*(.*)$/', $line, $matches)) {
        $key = trim($matches[1]);
        
        // If this key exists in our POST data, update it
        if (array_key_exists($key, $postData)) {
            $newValue = $postData[$key];
            
            // Validation
            if ($key === 'transaction_logging' && !in_array($newValue, ['a', 's', 'o'])) {
                $newContent .= $line; // Skip update if invalid
                continue;
            }
            if ($key === 'x_forwarded_for' && !in_array($newValue, ['yes', 'no'])) {
                $newContent .= $line; // Skip update if invalid
                continue;
            }

            $newContent .= "$key = $newValue\n";
            $updatedKeys[] = $key;
        } else {
            // Key not in POST (maybe unchecked checkbox? or just missing), keep original
            $newContent .= $line;
        }
    } else {
        // Comment or empty line, keep as is
        $newContent .= $line;
    }
}

// Write back to file
if (file_put_contents($configFile, $newContent) === false) {
    http_response_code(500);
    exit("Failed to write to config file.");
}

// Redirect back to index with success message
header("Location: index.php?status=success");
exit();
?>
