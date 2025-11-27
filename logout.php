<?php
session_start();

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Logging out...</title>
</head>
<body>
<script>
    // Trigger force logout in all tabs/windows
    localStorage.setItem('adminForceLogout', Date.now());
    
    // Clear the flag after a moment
    setTimeout(() => {
        localStorage.removeItem('adminForceLogout');
    }, 1000);
    
    // Redirect to login
    window.location.href = 'admin_login.php';
</script>
</body>
</html>