<?php
    // admin_dashboard.php - Complete Admin Panel with DB Integration and Modals
    session_start();
    
    // --- 1. DB Connection and Setup ---
    require 'db_con.php'; 
    $target_db = 'event_management'; 

    // --- 2. AUTHENTICATION AND AUTHORIZATION ---
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
        $_SESSION['admin_login_error'] = 'Access denied. Please log in.';
        header('Location: index.php'); // Redirect to admin login
        exit;
    }
    
    // 2.2. CRITICAL CHECK: Restrict access to non-admin users
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== TRUE) {
        $access_denied_message = 'Access Denied: You do not have administrator privileges to view this page.';
        error_log("Unauthorized Access Attempt: Non-admin user " . ($_SESSION['username'] ?? 'N/A') . " tried to access admin dashboard.");
        $_SESSION = array();
        session_destroy();
        session_start();
        $_SESSION['admin_login_error'] = $access_denied_message; 
        header('Location: index.php'); 
        exit;
    }

    $current_user = $_SESSION['username'] ?? 'Admin User';
    
    // --- 3. FLASH MESSAGE HANDLING ---
    $message = $_SESSION['message'] ?? null;
    unset($_SESSION['message']);
    
    // --- 4. DATA FETCHING ---
    $all_events = [];
    $all_users = [];
    $db_error_message = null;
    
    $pdo = null;
    try {
        $pdo = open_db_connection();
        // Fetch All Events from the 'events' table
        $event_sql = "SELECT id, event_name, date, number_of_tickets, status FROM {$target_db}.events ORDER BY date DESC";
        $event_stmt = $pdo->query($event_sql);
        $all_events = $event_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch All Users from the 'users' table
        $user_sql = "SELECT id, user_name, email, is_admin FROM {$target_db}.users ORDER BY id ASC";
        $user_stmt = $pdo->query($user_sql);
        $raw_users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($raw_users as $user) {
            $user['is_admin_display'] = ((bool)$user['is_admin'] === TRUE) ? 'Yes' : 'No';
            $all_users[] = $user;
        }

    } catch (\PDOException $e) {
        error_log("DB Fetch Error on Admin Dashboard: " . $e->getMessage());
        $db_error_message = "Could not load data: Database error occurred.";
    } finally {
        if ($pdo) { close_db_connection($pdo); }
    }
    
    // Determine which tab is active (default to 'users')
    $active_tab = $_GET['tab'] ?? 'users'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Event Management</title>
    <link rel="stylesheet" href="admin_dashboard_style.css">
     <script>
        // Function to close any modal when clicking the overlay
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
    <script src="user_management.js"></script>
    <script src="event_management.js"></script>
</head>
<body>
    <?php 
        include 'header.php'; 
    ?>
    <div class="scrollable-content-wrapper"> 
    <div class="dashboard-container">
        
        <h1>Administrator Panel</h1>
        <?php 
            if (isset($db_error_message)) {
                $message = ['type' => 'error', 'text' => $db_error_message];
            }
            if ($message): 
                $style = $message['type'] === 'success' ? 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;';
        ?>
            <p style="<?php echo $style; ?> padding: 10px; border-radius: 4px; font-weight: bold;">
                <?php echo htmlspecialchars($message['text']); ?>
            </p>
        <?php endif; ?>
        
        <div class="tabs">
            <a href="?tab=users" class="<?php echo $active_tab == 'users' ? 'active' : ''; ?>">
                User Management (<?php echo count($all_users); ?>)
            </a>
            <a href="?tab=events" class="<?php echo $active_tab == 'events' ? 'active' : ''; ?>">
                Event Management (<?php echo count($all_events); ?>)
            </a>
        </div>
        
        <div class="tab-content">
            <?php if ($active_tab == 'users'): ?>
                <h2>User Management</h2>
                <div style="text-align: right; margin-bottom: 15px;">
                    <button id="openCreateUserModal" class="create-btn">
                        + Add New User
                    </button>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Is Admin</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['is_admin_display']; ?></td>
                            <td>
                                <button class="action-btn edit-user-btn" 
                                        data-user-id="<?php echo $user['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($user['user_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                        data-is-admin="<?php echo $user['is_admin']; ?>">
                                    Edit
                                </button>
                                <form action="process_user.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete user ID <?php echo $user['id']; ?>?');">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif ($active_tab == 'events'): ?>
                <h2>Event Management</h2>
                <div style="text-align: right; margin-bottom: 15px;">
                    <button id="openCreateEventModal" class="create-btn">
                        + Create New Event
                    </button>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Tickets Available</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($all_events as $event): 
                            $current_status = htmlspecialchars($event['status'] ?? 'Active');
                            
                            if ($current_status === 'Cancelled') {
                                $next_action = 'reactivate';
                                $button_text = '✅ Reactivate';
                                $button_class = 'reactivate-btn';
                                $confirm_message = 'Are you sure you want to REACTIVATE event ID ' . $event['id'] . '?';
                            } else {
                                $next_action = 'cancel';
                                $button_text = '❌ Cancel Event';
                                $button_class = 'cancel-btn';
                                $confirm_message = 'Are you sure you want to CANCEL event ID ' . $event['id'] . '?';
                            }
                        ?>
                        <tr>
                            <td><?php echo $event['id']; ?></td>
                            <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($event['date']); ?></td>
                            <td><?php echo htmlspecialchars($event['number_of_tickets']); ?></td>
                            <td>
                                <span style="font-weight: bold; color: <?php echo $current_status === 'Cancelled' ? '#dc3545' : '#28a745'; ?>">
                                    <?php echo $current_status; ?>
                                </span>
                            </td>
                            <td>
                                <button class="action-btn edit-event-btn" 
                                        data-event-id="<?php echo $event['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($event['event_name']); ?>"
                                        data-date="<?php echo htmlspecialchars($event['date']); ?>"
                                        data-tickets="<?php echo htmlspecialchars($event['number_of_tickets']); ?>">
                                    Edit
                                </button>
                                
                                <form action="process_event.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="<?php echo $next_action; ?>">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" class="<?php echo $button_class; ?>" onclick="return confirm('<?php echo $confirm_message; ?>');">
                                        <?php echo $button_text; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div> 
    </div> 
    </div>

    <?php include 'footer.php'; ?>
    
    <div id="eventModal" class="modal-overlay">
        <div class="modal-content">
            <a href="#" class="close-btn" onclick="document.getElementById('eventModal').style.display='none'; return false;">&times;</a>
            <h2 id="modalTitle">Create New Event</h2>
            <p>Enter the details for the event.</p>

            <form action="process_event.php" method="POST">
                <input type="hidden" name="action" id="eventAction" value="create">
                <input type="hidden" name="event_id" id="eventID" value="">
                <label for="event_name">Event Name:</label>
                <input type="text" id="event_name" name="event_name" required>
                <label for="event_date">Date:</label>
                <input type="date" id="event_date" name="event_date" required>
                <label for="num_tickets">Total Tickets:</label>
                <input type="number" id="num_tickets" name="number_of_tickets" min="1" value="100" required>   
                <button type="submit" id="modalSubmitBtn">Create Event</button>
            </form>

        </div>
    </div>

    <div id="userModal" class="modal-overlay">
        <div class="modal-content">
            <a href="#" class="close-btn" onclick="document.getElementById('userModal').style.display='none'; return false;">&times;</a>
            <h2 id="userModalTitle">Add New User</h2>
            
            <form action="process_user.php" method="POST">
                
                <input type="hidden" name="action" id="userAction" value="create">
                <input type="hidden" name="user_id" id="userIdField" value="">

                <label for="user_name">Username:</label>
                <input type="text" id="user_name" name="username" required>

                <label for="user_email">Email:</label>
                <input type="email" id="user_email" name="email" required>

                <label for="user_password">Password:</label>
                <input type="password" id="user_password" name="password">
                
                <div style="margin-top: 15px;">
                    <input type="checkbox" id="user_is_admin" name="is_admin" value="1" style="width: auto; margin-right: 5px;">
                    <label for="user_is_admin" style="display: inline;">Grant Administrator Privileges</label>
                </div>

                <button type="submit" id="userModalSubmitBtn">Add User</button>
            </form>
        </div>
    </div>

</body>
</html>