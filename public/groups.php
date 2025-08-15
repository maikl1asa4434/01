<?php
// public/groups.php

require_once __DIR__ . '/../app/session.php';
require_login();
require_once __DIR__ . '/../app/db.php';

$feedback_message = '';
$error_message = '';

// Handle group creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_name'])) {
    $group_name = trim($_POST['group_name']);

    if (empty($group_name)) {
        $error_message = "Group name cannot be empty.";
    } else {
        try {
            // Check if group already exists
            $stmt = $pdo->prepare("SELECT id FROM groups WHERE name = :name");
            $stmt->execute([':name' => $group_name]);
            if ($stmt->fetch()) {
                $error_message = "A group with this name already exists.";
            } else {
                // Insert new group
                $insertStmt = $pdo->prepare("INSERT INTO groups (name) VALUES (:name)");
                $insertStmt->execute([':name' => $group_name]);
                $feedback_message = "Group '" . htmlspecialchars($group_name) . "' created successfully.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all existing groups
$groups = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Could not fetch groups: " . $e->getMessage();
}

require_once __DIR__ . '/../app/partials/header.php';
?>

<style>
    .form-container {
        background-color: #fff;
        padding: 2rem;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
        max-width: 500px;
    }
    .form-container input[type="text"] {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 3px;
        margin-right: 1rem;
    }
    .form-container button {
        padding: 0.5rem 1rem;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }
    .form-container button:hover {
        background-color: #218838;
    }
    .form-group {
        display: flex;
        align-items: center;
    }
    .feedback {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 5px;
    }
    .feedback.success {
        background-color: #d4edda;
        color: #155724;
    }
    .feedback.error {
        background-color: #f8d7da;
        color: #721c24;
    }
    .groups-list {
        list-style-type: none;
        padding: 0;
    }
    .groups-list li {
        background-color: #fff;
        padding: 1rem;
        border-bottom: 1px solid #eee;
    }
    .groups-list li:first-child {
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
    }
    .groups-list li:last-child {
        border-bottom: none;
        border-bottom-left-radius: 5px;
        border-bottom-right-radius: 5px;
    }
</style>

<h1>Group Management</h1>

<div class="form-container">
    <h2>Create New Group</h2>
    <?php if ($feedback_message): ?>
        <div class="feedback success"><?php echo $feedback_message; ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="feedback error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <form action="groups.php" method="POST">
        <div class="form-group">
            <input type="text" name="group_name" placeholder="Enter new group name" required>
            <button type="submit">Create Group</button>
        </div>
    </form>
</div>

<h2>Existing Groups</h2>
<?php if (empty($groups)): ?>
    <p>No groups have been created yet.</p>
<?php else: ?>
    <ul class="groups-list">
        <?php foreach ($groups as $group): ?>
            <li><?php echo htmlspecialchars($group['name']); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
require_once __DIR__ . '/../app/partials/footer.php';
?>
