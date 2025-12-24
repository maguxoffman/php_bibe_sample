<?php
// config.php

// Simple function to parse .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Only set if not already in system env
            if (getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Load .env
loadEnv(__DIR__ . '/config.env');

// Resolve Config Path
// Priority: System Env > .env > Default
$envPath = getenv('XDOS_CFG_PATH');
$configFile = $envPath !== false ? $envPath : __DIR__ . '/DATA/xdos.cfg';

// Optional: Validate config file existence here or in consuming files
?>
