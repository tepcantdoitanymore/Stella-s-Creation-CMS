<?php
/**
 * Admin Security Helper
 * Include this file at the top of every admin page for enhanced security
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function admin_require_login() {
    if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
        header('Location: admin_login.php?error=not_logged_in');
        exit;
    }

    $timeout_duration = 1800; // 30 minutes in seconds
    if (isset($_SESSION['admin_last_activity'])) {
        $elapsed_time = time() - $_SESSION['admin_last_activity'];
        if ($elapsed_time > $timeout_duration) {
            session_unset();
            session_destroy();
            header('Location: admin_login.php?error=session_expired');
            exit;
        }
    }

    $_SESSION['admin_last_activity'] = time();

    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($_SESSION['admin_ip'])) {
        if ($_SESSION['admin_ip'] !== $current_ip) {
            // IP address changed - possible session hijacking
            session_unset();
            session_destroy();
            header('Location: admin_login.php?error=ip_mismatch');
            exit;
        }
    } else {
        $_SESSION['admin_ip'] = $current_ip;
    }

    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (isset($_SESSION['admin_user_agent'])) {
        if ($_SESSION['admin_user_agent'] !== $current_ua) {
            // User agent changed - possible session hijacking
            session_unset();
            session_destroy();
            header('Location: admin_login.php?error=browser_mismatch');
            exit;
        }
    } else {
        $_SESSION['admin_user_agent'] = $current_ua;
    }
    if (!isset($_SESSION['admin_last_regeneration'])) {
        $_SESSION['admin_last_regeneration'] = time();
    } else {
        $regen_interval = 600; // 10 minutes
        if (time() - $_SESSION['admin_last_regeneration'] > $regen_interval) {
            session_regenerate_id(true);
            $_SESSION['admin_last_regeneration'] = time();
        }
    }
}

admin_require_login();