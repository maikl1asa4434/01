<?php
// public/login.php

// If the user is already logged in, redirect them to the dashboard.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../app/db.php';

$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $error_message = 'Username and password are required.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Redirect to the main dashboard
                header('Location: index.php');
                exit;
            } else {
                $error_message = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'A database error occurred. Please try again later.';
            // In production, log the error: error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4; }
        .login-container { background-color: #fff; padding: 2rem; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .login-container h1 { text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 3px; }
        .error { color: #d9534f; margin-bottom: 1rem; text-align: center; }
        button { width: 100%; padding: 0.75rem; background-color: #0275d8; color: #fff; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background-color: #025aa5; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Admin Panel Login</h1>
        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
