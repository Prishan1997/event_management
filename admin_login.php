<?php
    // admin_login.php (Combined Form Display and Authentication)
    session_start();
    
    // Include the connection functions file (e.g., db_con.php)
    require 'db_con.php'; 

    $login_error = '';

    // --- 1. PRE-CHECK: Redirect if already logged in as Admin ---
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === TRUE && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === TRUE) {
        header('Location: admin_dashboard.php');
        exit;
    }

    // --- 2. AUTHENTICATION LOGIC ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $target_db = 'event_management'; 
        
        if (empty($username) || empty($password)) {
            $login_error = 'Please enter both username and password.';
        } else {
            $pdo = null; 
            try {
                $pdo = open_db_connection(); 

                // Select the required user data
                $sql = "SELECT id, user_name, password, is_admin FROM {$target_db}.users WHERE user_name = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                close_db_connection($pdo); 
                
                // 1. Basic Credentials Check (Plaintext comparison)
                if ($user && $password === $user['password']) {
                    
                    // 2. CRITICAL ADMIN CHECK
                    if ((bool)$user['is_admin'] === TRUE) {
                        // Success: Create session variables and redirect
                        $_SESSION['loggedin'] = TRUE;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['user_name']; 
                        $_SESSION['is_admin'] = TRUE; 
                        
                        header('Location: admin_dashboard.php');
                        exit;
                    } else {
                        // User is valid but is NOT an admin
                        $login_error = 'Access Denied: Only administrators can log in here.';
                        // Do NOT create session variables if they are not an admin
                    }

                } else {
                    // Failure: Invalid username or password
                    $login_error = 'Invalid username or password.';
                }

            } catch (\PDOException $e) {
                if ($pdo) { close_db_connection($pdo); }
                $login_error = 'A system error occurred. Please try again later.';
                error_log("Admin Login DB Error: " . $e->getMessage()); 
            }
        }
    }
    
    // --- 3. ERROR MESSAGE DISPLAY LOGIC ---
    // If the error message was set via a redirect from dashboard.php, use that.
    if (isset($_SESSION['admin_login_error'])) {
        $login_error = $_SESSION['admin_login_error'];
        unset($_SESSION['admin_login_error']); 
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
   <link rel="stylesheet" href="login_style.css">
</head>
<body>
    <div class="login-container">
        <h2>🔒 Admin Login</h2>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <label for="username">Admin Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Log in as Admin</button>
        </form>

        <?php
            // Display the error message
            if (!empty($login_error)) {
                echo '<p class="message">' . htmlspecialchars($login_error) . '</p>';
            }
        ?>
    </div>
</body>
</html>