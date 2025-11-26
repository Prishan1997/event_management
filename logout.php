<?php
    // logout.php
    session_start();

    // Determine the target login page before destroying the session
    $redirect_page = 'index.php'; // Default redirect for non-admins/general users

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect back to the determined login page
    header('Location: ' . $redirect_page);
    exit;
?>