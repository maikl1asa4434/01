<?php
// public/view_group.php

require_once __DIR__ . '/../app/session.php';
require_login();
require_once __DIR__ . '/../app/db.php';

// --- Input Validation ---
$group_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$group_id) {
    // If no ID is provided or it's invalid, redirect to the dashboard.
    header('Location: index.php');
    exit;
}

$group_name = '';
$computers = [];
$error_message = '';

try {
    // Fetch the group's name
    $stmt = $pdo->prepare("SELECT name FROM groups WHERE id = :id");
    $stmt->execute([':id' => $group_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        $error_message = "Group not found.";
    } else {
        $group_name = $group['name'];

        // Fetch all computers in this group along with their last check-in time
        $sql = "
            SELECT
                c.computer_name,
                c.hwid,
                MAX(ch.checkin_time) AS last_seen
            FROM
                computers c
            LEFT JOIN
                checkins ch ON c.id = ch.computer_id
            WHERE
                c.group_id = :group_id
            GROUP BY
                c.id, c.computer_name, c.hwid
            ORDER BY
                last_seen DESC, c.computer_name;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':group_id' => $group_id]);
        $computers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

require_once __DIR__ . '/../app/partials/header.php';
?>

<h1>Computers in Group: <?php echo htmlspecialchars($group_name); ?></h1>

<?php if ($error_message): ?>
    <p style="color: red;"><?php echo $error_message; ?></p>
<?php elseif (empty($computers)): ?>
    <p>No computers have been registered in this group yet.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Computer Name</th>
                <th>Hardware ID (HWID)</th>
                <th>Last Seen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($computers as $computer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($computer['computer_name']); ?></td>
                    <td><?php echo htmlspecialchars($computer['hwid']); ?></td>
                    <td>
                        <?php
                        if ($computer['last_seen']) {
                            echo htmlspecialchars($computer['last_seen']) . ' UTC';
                        } else {
                            echo 'Never';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<br>
<a href="index.php">&larr; Back to Dashboard</a>

<?php
require_once __DIR__ . '/../app/partials/footer.php';
?>
