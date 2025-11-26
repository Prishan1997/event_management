<?php
    // login.php (Updated to handle admin status)
    session_start();
    
    // Existing logic to redirect if already logged in...
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === TRUE) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Include the connection functions file
    require 'db_con.php'; 

    $login_error = '';

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

                // CRITICAL CHANGE: Select the new 'is_admin' column
                $sql = "SELECT id, user_name, password, is_admin FROM {$target_db}.users WHERE user_name = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                close_db_connection($pdo); 
                
                if ($user && $password === $user['password']) {
                    // Success: Create session variables
                    $_SESSION['loggedin'] = TRUE;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['user_name']; 
                    
                    // CRITICAL CHANGE: Store the admin status in the session
                    $_SESSION['is_admin'] = (bool)$user['is_admin']; 
                    
                    // Redirect to the protected dashboard
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
    
    // Display error message from previous attempt or unauthorized access attempt
    if (isset($_SESSION['login_error'])) {
        $login_error = $_SESSION['login_error'];
        unset($_SESSION['login_error']); 
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="login_style.css">
</head>
<body>
    <div class="login-container">
        <h2>🔒 Login</h2>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <?php
            // Display the error message collected from the logic above
            if (!empty($login_error)) {
                echo '<p class="message">' . htmlspecialchars($login_error) . '</p>';
            }
        ?>
    </div>
</body>
</html>