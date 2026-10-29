<?php
/**
 * CampusPark – Logout Handler
 * Clears user session, destroys session cookies, and redirects safely to homepage
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Safe logout function destroys session data
logout_user();

// Start a fresh session just for the flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

set_flash('info', 'You have been safely signed out of CampusPark.');
header("Location: " . BASE_URL . "/index.php");
exit;
