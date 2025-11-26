<?php
    // process_event.php - Handles Event CRUD operations including Status updates
    session_start();

    // Ensure only logged-in admins can access this script
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== TRUE) {
        header('Location: admin_login.php');
        exit;
    }

    require 'db_con.php'; 
    $target_db = 'event_management'; 

    // Initialize variables
    $action = $_POST['action'] ?? '';
    $event_id = $_POST['event_id'] ?? null;
    $redirect_url = 'admin_dashboard.php?tab=events'; 

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . $redirect_url);
        exit;
    }

    $pdo = null;
    try {
        $pdo = open_db_connection();
        
        // --- CREATE / EDIT (UPSERT) OPERATION ---
        if ($action === 'create' || $action === 'edit') {
            
            // Collect and sanitize input data
            $event_name = trim($_POST['event_name'] ?? '');
            $event_date = trim($_POST['event_date'] ?? '');
            $num_tickets = filter_var($_POST['number_of_tickets'] ?? 0, FILTER_VALIDATE_INT);
            $new_status = 'Active'; // New events are always active

            if (empty($event_name) || empty($event_date) || $num_tickets === FALSE || $num_tickets < 1) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Event Name, Date, and valid Ticket count are required.'];
                header('Location: ' . $redirect_url);
                exit;
            }

            if ($action === 'create') {
                // INSERT new event (setting status to Active)
                $sql = "INSERT INTO {$target_db}.events (event_name, date, number_of_tickets, status) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$event_name, $event_date, $num_tickets, $new_status]);
                
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Event "' . htmlspecialchars($event_name) . '" created successfully with status: ' . $new_status];

            } elseif ($action === 'edit' && $event_id) {
                // UPDATE existing event. We do NOT update the status during a standard edit modal save.
                $sql = "UPDATE {$target_db}.events SET event_name = ?, date = ?, number_of_tickets = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$event_name, $event_date, $num_tickets, $event_id]);
                
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Event ID ' . $event_id . ' details updated successfully.'];
            }
        
        // --- STATUS CHANGE OPERATIONS ---
        } elseif ($event_id && ($action === 'cancel' || $action === 'reactivate')) {
            
            $new_status = ($action === 'cancel') ? 'Cancelled' : 'Active';

            // Update the status
            $sql = "UPDATE {$target_db}.events SET status = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_status, $event_id]);
            
            // Log and set success message
            if ($stmt->rowCount() > 0) {
                 $_SESSION['message'] = ['type' => 'success', 'text' => 'Event ID ' . $event_id . ' status successfully changed to "' . $new_status . '".'];
            } else {
                 $_SESSION['message'] = ['type' => 'error', 'text' => 'Event ID ' . $event_id . ' not found or status already set to "' . $new_status . '".'];
            }
            
        } else {
            // Invalid action or missing ID
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid action or missing required event data.'];
        }

    } catch (\PDOException $e) {
        // Handle database errors
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Database error: Could not process event request.'];
        error_log("Event CRUD Error: " . $e->getMessage());

    } finally {
        if ($pdo) { close_db_connection($pdo); }
    }

    // Final redirection
    header('Location: ' . $redirect_url);
    exit;
?>