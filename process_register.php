<?php
    // process_register.php - Handles new user creation
    session_start();
    require 'db_con.php'; 
    
    $target_db = 'event_management';
    $redirect_login = 'index.php';
    $redirect_dashboard = 'dashboard.php'

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . $redirect_login . '?mode=register');
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Validation ---
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['register_error'] = "All fields are required.";
        header('Location: ' . $redirect_login . '?mode=register');
        exit;
    }
    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = "Passwords do not match.";
        header('Location: ' . $redirect_login . '?mode=register');
        exit;
    }
    if (strlen($password) < 6) {
         $_SESSION['register_error'] = "Password must be at least 6 characters long.";
        header('Location: ' . $redirect_login . '?mode=register');
        exit;
    }

    // --- Database Check and Insertion ---
    $pdo = null;
    try {
        $pdo = open_db_connection();

        // Check if username or email already exists (using positional placeholders '?' is safer)
        $check_sql = "SELECT id FROM {$target_db}.users WHERE user_name = ? OR email = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$username, $email]);
        
        if ($check_stmt->rowCount() > 0) {
            $_SESSION['register_error'] = "Username or Email is already in use.";
            header('Location: ' . $redirect_login . '?mode=register');
            exit;
        }
        
        // Insert new user (is_admin defaults to 0/FALSE)
        $insert_sql = "INSERT INTO {$target_db}.users (user_name, email, password, is_admin) VALUES (?, ?, ?, 0)";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([$username, $email, $password]);

        // Success
        $_SESSION['login_success'] = "Registration successful! Please log in.";
        header('Location: ' . $redirect_dashboard);
        exit;

    } catch (\PDOException $e) {
        error_log("Registration DB Error: " . $e->getMessage());
        $_SESSION['register_error'] = "A server error occurred during registration.";
        header('Location: ' . $redirect_login . '?mode=register');
        exit;
    } finally {
        if ($pdo) { close_db_connection($pdo); }
    }