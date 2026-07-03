<?php

// Check composer autoloader or fallback to manual require
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once __DIR__ . '/src/Terminal.php';
}

// Instantiate the terminal
$terminal = new \SrvKit\Terminal\Terminal([
    'password' => 'CS8854', // Default password matching the prompt screenshot
]);

// Handle requests and render the UI
$terminal->handle();
