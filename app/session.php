<?php
// app/session.php

// Start the session if it's not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is logged in.
 * If not, it redirects them to the login page and terminates the script.
 */
function require_login() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}
?>
