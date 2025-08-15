<?php
// app/partials/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            background-color: #f8f9fa;
            color: #333;
        }
        .navbar {
            background-color: #343a40;
            padding: 1rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
        }
        .navbar a:hover {
            background-color: #495057;
            border-radius: 5px;
        }
        .navbar .logo {
            font-size: 1.25rem;
            font-weight: bold;
        }
        .navbar .user-info {
            font-size: 0.9rem;
        }
        .container {
            padding: 2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background-color: #e9ecef;
        }
        tr:hover {
            background-color: #f1f3f5;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">
        <a href="index.php">Status Panel</a>
    </div>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="groups.php">Groups</a>
        <a href="generator.php">Script Generator</a>
    </nav>
    <div class="user-info">
        Logged in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
