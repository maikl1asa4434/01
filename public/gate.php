<?php
// public/gate.php

// Include the database connection
require_once __DIR__ . '/../app/db.php';

// --- Input Validation ---

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die("Method Not Allowed");
}

// Get POST data
$group_id = isset($_POST['group_id']) ? trim($_POST['group_id']) : '';
$hwid = isset($_POST['hwid']) ? trim($_POST['hwid']) : '';
$computer_name = isset($_POST['computer_name']) ? trim($_POST['computer_name']) : '';

// Basic validation: ensure required fields are not empty
if (empty($group_id) || empty($hwid)) {
    http_response_code(400); // Bad Request
    die("Bad Request: group_id and hwid are required.");
}

try {
    // --- Group Validation ---
    $stmt = $pdo->prepare("SELECT id FROM groups WHERE id = :group_id");
    $stmt->execute([':group_id' => $group_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        http_response_code(403); // Forbidden
        die("Forbidden: Invalid Group ID.");
    }

    // --- Computer Handling ---
    $computer_id = null;

    // Check if a computer with this HWID already exists
    $stmt = $pdo->prepare("SELECT id, computer_name FROM computers WHERE hwid = :hwid");
    $stmt->execute([':hwid' => $hwid]);
    $computer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($computer) {
        // Computer exists, get its ID
        $computer_id = $computer['id'];

        // If the computer name has changed, update it
        if ($computer['computer_name'] !== $computer_name) {
            $updateStmt = $pdo->prepare("UPDATE computers SET computer_name = :computer_name WHERE id = :id");
            $updateStmt->execute([':computer_name' => $computer_name, ':id' => $computer_id]);
        }
    } else {
        // Computer does not exist, create it
        $insertStmt = $pdo->prepare("INSERT INTO computers (hwid, group_id, computer_name) VALUES (:hwid, :group_id, :computer_name)");
        $insertStmt->execute([
            ':hwid' => $hwid,
            ':group_id' => $group_id,
            ':computer_name' => $computer_name
        ]);
        // Get the ID of the newly created computer
        $computer_id = $pdo->lastInsertId();
    }

    // --- Log the Check-in ---
    if ($computer_id) {
        $checkinStmt = $pdo->prepare("INSERT INTO checkins (computer_id) VALUES (:computer_id)");
        $checkinStmt->execute([':computer_id' => $computer_id]);
    }

    // --- Respond ---
    http_response_code(200); // OK
    echo "OK";

} catch (PDOException $e) {
    // If any database error occurs, return a server error
    http_response_code(500);
    // In production, log this error to a file instead of echoing it.
    die("Server Error: " . $e->getMessage());
}
?>
