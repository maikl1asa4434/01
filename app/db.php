<?php
// app/db.php

// Define the path to the SQLite database
$dbPath = __DIR__ . '/database.sqlite';
$pdo = null;

try {
    // Create a new PDO instance to connect to the database
    $pdo = new PDO('sqlite:' . $dbPath);
    // Set the PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If connection fails, stop the script and show an error.
    // In a real production environment, you might want to log this error instead of showing it.
    http_response_code(500);
    die("Database connection failed: " . $e->getMessage());
}
?>
