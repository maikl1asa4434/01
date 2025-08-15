<?php
// public/index.php

require_once __DIR__ . '/../app/session.php';
require_login();
require_once __DIR__ . '/../app/db.php';

// --- Data Fetching ---
$statistics = [];
try {
    // This query calculates all statistics for each group in one go.
    $sql = "
        SELECT
            g.id AS group_id,
            g.name AS group_name,
            COUNT(DISTINCT c.id) AS all_computers,
            COUNT(DISTINCT CASE WHEN ch.checkin_time >= DATETIME('now', '-2 minutes') THEN c.id END) AS online_now,
            COUNT(DISTINCT CASE WHEN ch.checkin_time >= DATETIME('now', '-1 hour') THEN c.id END) AS online_1_hour,
            COUNT(DISTINCT CASE WHEN ch.checkin_time >= DATETIME('now', '-12 hours') THEN c.id END) AS online_12_hours,
            COUNT(DISTINCT CASE WHEN ch.checkin_time >= DATETIME('now', '-24 hours') THEN c.id END) AS online_24_hours,
            COUNT(DISTINCT CASE WHEN ch.checkin_time >= DATETIME('now', '-7 days') THEN c.id END) AS online_7_days
        FROM
            groups g
        LEFT JOIN
            computers c ON g.id = c.group_id
        LEFT JOIN
            checkins ch ON c.id = ch.computer_id
        GROUP BY
            g.id, g.name
        ORDER BY
            g.name;
    ";
    $stmt = $pdo->query($sql);
    $statistics = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // For the user, show a friendly message. Log the actual error for the admin.
    die("Error fetching statistics: " . $e->getMessage());
}

// --- Page Render ---
require_once __DIR__ . '/../app/partials/header.php';
?>

<h1>Dashboard</h1>
<h2 style="font-weight: normal;">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
<p>This table shows the online status of computer groups.</p>

<table>
    <thead>
        <tr>
            <th>Group</th>
            <th>All</th>
            <th>Online</th>
            <th>Online %</th>
            <th>1 Hour</th>
            <th>12 Hours</th>
            <th>24 Hours</th>
            <th>Week</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($statistics)): ?>
            <tr>
                <td colspan="8" style="text-align: center;">No groups found. Please create a group first.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($statistics as $group): ?>
                <tr>
                    <td>
                        <a href="view_group.php?id=<?php echo htmlspecialchars($group['group_id']); ?>">
                            <?php echo htmlspecialchars($group['group_name']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($group['all_computers']); ?></td>
                    <td><?php echo htmlspecialchars($group['online_now']); ?></td>
                    <td>
                        <?php
                        if ($group['all_computers'] > 0) {
                            $online_percentage = ($group['online_now'] / $group['all_computers']) * 100;
                            echo number_format($online_percentage, 2) . '%';
                        } else {
                            echo '0.00%';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($group['online_1_hour']); ?></td>
                    <td><?php echo htmlspecialchars($group['online_12_hours']); ?></td>
                    <td><?php echo htmlspecialchars($group['online_24_hours']); ?></td>
                    <td><?php echo htmlspecialchars($group['online_7_days']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
require_once __DIR__ . '/../app/partials/footer.php';
?>
