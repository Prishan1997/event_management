<?php
    // process_login.php - Central Authentication Handler (INSECURE - TEMPORARY FIX)
    session_start();
    
    // Redirect if already logged in 
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === TRUE) {
        header('Location: dashboard.php'); 
        exit;
    }
    
    require 'db_con.php'; 

    $login_error = '';
    $target_db = 'event_management'; 

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $username_or_email = trim($_POST['username'] ?? ''); 
        $password = $_POST['password'] ?? '';
        
        if (empty($username_or_email) || empty($password)) {
            $login_error = 'Please enter both username/email and password.';
        } else {
            $pdo = null; 
            try {
                $pdo = open_db_connection(); 

                // CORRECTED SQL: Only check the user_name column
                $sql = "SELECT id, user_name, password, is_admin FROM {$target_db}.users WHERE user_name = ?";
                
                $stmt = $pdo->prepare($sql);
                
                // CORRECTED EXECUTE: Only pass the input variable once
                $stmt->execute([$username_or_email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                close_db_connection($pdo);
                
                // Compare the plain-text password directly
                if ($user && $password === $user['password']) {
                    
                    // Success: Create session variables
                    $_SESSION['loggedin'] = TRUE;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['user_name']; 
                    $_SESSION['is_admin'] = (bool)$user['is_admin']; 
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $login_error = 'Invalid username or password.';
                }

            } catch (\PDOException $e) {
                if ($pdo) { close_db_connection($pdo); }
                $login_error = 'A system error occurred. Please try again later.';
                error_log("Login DB Error: " . $e->getMessage()); 
            }
        }
    }
    
    if (isset($_SESSION['login_error'])) {
        $login_error = $_SESSION['login_error'];
        unset($_SESSION['login_error']); 
    }
    
    if ($login_error) {
         $_SESSION['login_error'] = $login_error;
         header('Location: index.php');
         exit;
    }
    
    header('Location: index.php');
    exit;
?>