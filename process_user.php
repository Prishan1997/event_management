<?php
    // process_user.php - Handles all User CRUD operations
    session_start();

    // Ensure only logged-in admins can access this script
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== TRUE) {
        // Redirect non-admins or non-logged-in users
        header('Location: admin_login.php');
        exit;
    }

    // Include the database connection file
    require 'db_con.php'; 
    $target_db = 'event_management'; 

    // Initialize variables
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    $redirect_url = 'admin_dashboard.php?tab=users'; // Default redirect location

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Must be a POST request to process data
        header('Location: ' . $redirect_url);
        exit;
    }

    $pdo = null;
    try {
        $pdo = open_db_connection();
        
        // --- CREATE / EDIT (UPSERT) OPERATION ---
        if ($action === 'create' || $action === 'edit') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? ''; // Note: Use password hashing in a real app!
            // 'is_admin' is checked if the checkbox was submitted (i.e., if it was checked)
            $is_admin = isset($_POST['is_admin']) ? 1 : 0; 

            if (empty($username) || empty($email)) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Username and Email are required.'];
                header('Location: ' . $redirect_url);
                exit;
            }

            if ($action === 'create') {
                if (empty($password)) {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Password is required for new users.'];
                    header('Location: ' . $redirect_url);
                    exit;
                }
                
                // INSERT new user
                $sql = "INSERT INTO {$target_db}.users (user_name, email, password, is_admin) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $email, $password, $is_admin]);
                
                $_SESSION['message'] = ['type' => 'success', 'text' => 'User "' . htmlspecialchars($username) . '" created successfully.'];

            } elseif ($action === 'edit' && $user_id) {
                // UPDATE existing user
                $update_fields = "user_name = ?, email = ?, is_admin = ?";
                $params = [$username, $email, $is_admin];

                // Check if password field was submitted (only update if provided)
                if (!empty($password)) {
                    $update_fields .= ", password = ?";
                    $params[] = $password; 
                }

                $sql = "UPDATE {$target_db}.users SET {$update_fields} WHERE id = ?";
                $params[] = $user_id;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                $_SESSION['message'] = ['type' => 'success', 'text' => 'User ID ' . $user_id . ' updated successfully.'];
            }
        
        // --- DELETE OPERATION ---
        } elseif ($action === 'delete' && $user_id) {
            
            // Prevent admin from deleting themselves! (Optional, but safe)
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'You cannot delete your own admin account.'];
            } else {
                $sql = "DELETE FROM {$target_db}.users WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id]);
                
                $_SESSION['message'] = ['type' => 'success', 'text' => 'User ID ' . $user_id . ' deleted successfully.'];
            }
        } else {
            // Invalid action or missing ID
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid action or missing required user ID.'];
        }

    } catch (\PDOException $e) {
        // Handle database errors
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Database error: Could not process request.'];
        error_log("User CRUD Error: " . $e->getMessage());

    } finally {
        if ($pdo) { close_db_connection($pdo); }
    }

    // Final redirection back to the user management tab
    header('Location: ' . $redirect_url);
    exit;
?>