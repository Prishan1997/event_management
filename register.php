<?php
    // register.php - Dedicated Registration Screen
    session_start();

    // If the user is already logged in, redirect to the dashboard
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === TRUE) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Check for registration errors from the processor
    $error = $_SESSION['register_error'] ?? null;
    unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Event Management</title>
    <link rel="stylesheet" href="login_style.css">
    <style>
        .error { color: red; margin-bottom: 15px; font-weight: bold; }
        .register-container {
            width: 350px; 
            margin: 50px auto; 
            padding: 20px; 
            border: 1px solid #ccc; 
            border-radius: 8px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }
        .register-container input[type="text"], 
        .register-container input[type="email"], 
        .register-container input[type="password"] {
            width: 100%; 
            padding: 10px; 
            margin-bottom: 10px; 
            border: 1px solid #ddd; 
            border-radius: 4px;
            box-sizing: border-box;
        }
        .register-container button { 
            width: 100%; 
            padding: 10px; 
            background-color: #28a745; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
        }
        .switch-link { margin-top: 20px; text-align: center; }
        .switch-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register New Account</h2>

        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form action="process_register.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password (min 6 chars)" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Register Account</button>
        </form>
        
        <div class="switch-link">
            Already have an account? <a href="index.php">Log In here.</a>
        </div>
    </div>
</body>
</html>