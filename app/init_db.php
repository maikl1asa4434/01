<?php
// app/init_db.php

// Define the path to the SQLite database
$dbPath = __DIR__ . '/database.sqlite';

// Create a new PDO instance to connect to the database
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database created successfully.\n";
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// SQL statements to create the tables
$commands = [
    // Users table for admin login
    'CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL
    )',
    // Groups table to organize computers
    'CREATE TABLE IF NOT EXISTS groups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    )',
    // Computers table to store unique machine info
    'CREATE TABLE IF NOT EXISTS computers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        hwid TEXT NOT NULL UNIQUE,
        group_id INTEGER NOT NULL,
        computer_name TEXT,
        FOREIGN KEY (group_id) REFERENCES groups(id)
    )',
    // Checkins table to log every ping from a computer
    'CREATE TABLE IF NOT EXISTS checkins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        computer_id INTEGER NOT NULL,
        checkin_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (computer_id) REFERENCES computers(id)
    )'
];

// Execute each SQL statement
foreach ($commands as $command) {
    try {
        $pdo->exec($command);
    } catch (PDOException $e) {
        die("Error executing command: " . $e->getMessage());
    }
}

echo "Tables created successfully.\n";

// Add the default admin user
$username = 'admin';
// It is critical to hash passwords. 'password' is a placeholder.
$password = 'password';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if the user already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
$stmt->execute([':username' => $username]);
if ($stmt->fetch()) {
    echo "Admin user already exists.\n";
} else {
    // Insert the new admin user
    $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
    try {
        $stmt->execute([':username' => $username, ':password' => $hashedPassword]);
        echo "Admin user created successfully with username 'admin' and password 'password'. Please change it immediately.\n";
    } catch (PDOException $e) {
        die("Could not create admin user: " . $e->getMessage());
    }
}

?>
