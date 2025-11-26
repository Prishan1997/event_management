<?php
    // process_booking.php - Handles user-initiated booking CRUD operations
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
        $_SESSION['login_error'] = 'Please log in to manage bookings.';
        header('Location: login.php');
        exit;
    }
    
    require 'db_con.php'; 
    $target_db = 'event_management'; 
    $user_id = $_SESSION['user_id'] ?? null;
    $redirect_url = 'dashboard.php'; 

    if (!$user_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid request or user data missing.'];
        header('Location: ' . $redirect_url);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $pdo = null;

    try {
        $pdo = open_db_connection();
        $pdo->beginTransaction(); // Start transaction for data integrity

        // --- 1. NEW BOOKING ACTION ---
        if ($action === 'new_booking') {
            
            $event_id = filter_var($_POST['event_id'], FILTER_VALIDATE_INT);
            $tickets = filter_var($_POST['tickets'], FILTER_VALIDATE_INT);

            if (!$event_id || $tickets === FALSE || $tickets < 1) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid event or ticket count provided.'];
            } else {
                
                // CRITICAL STEP 1: Check available tickets and event status
                $check_sql = "SELECT number_of_tickets, status FROM {$target_db}.events WHERE id = ?";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$event_id]);
                $event_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$event_data || $event_data['status'] !== 'Active') {
                     $_SESSION['message'] = ['type' => 'error', 'text' => 'Event is not available for booking (may be cancelled).'];
                } elseif ($event_data['number_of_tickets'] < $tickets) {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Only ' . $event_data['number_of_tickets'] . ' tickets remaining.'];
                } else {
                    // STEP 2: Insert Booking
                    $insert_sql = "INSERT INTO {$target_db}.bookings (user_id, event_id, tickets_booked) VALUES (?, ?, ?)";
                    $insert_stmt = $pdo->prepare($insert_sql);
                    $insert_stmt->execute([$user_id, $event_id, $tickets]);

                    // STEP 3: Update Event ticket count (decrement)
                    $update_sql = "UPDATE {$target_db}.events SET number_of_tickets = number_of_tickets - ? WHERE id = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_stmt->execute([$tickets, $event_id]);
                    
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Successfully booked ' . $tickets . ' tickets!'];
                    $pdo->commit(); 
                }
            }

        // --- 2. CANCEL BOOKING ACTION ---
        } elseif ($action === 'cancel_user_booking') {
            
            $booking_id = filter_var($_POST['booking_id'], FILTER_VALIDATE_INT);
            
            // CRITICAL STEP 1: Get booking details (tickets and event_id) and verify user ownership
            $details_sql = "SELECT event_id, tickets_booked FROM {$target_db}.bookings WHERE id = ? AND user_id = ?";
            $details_stmt = $pdo->prepare($details_sql);
            $details_stmt->execute([$booking_id, $user_id]);
            $booking_details = $details_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking_details) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Booking not found or you do not have permission to cancel it.'];
            } else {
                $event_id = $booking_details['event_id'];
                $tickets_to_return = $booking_details['tickets_booked'];

                // STEP 2: Delete Booking record
                $delete_sql = "DELETE FROM {$target_db}.bookings WHERE id = ?";
                $delete_stmt = $pdo->prepare($delete_sql);
                $delete_stmt->execute([$booking_id]);
                
                // STEP 3: Update Event ticket count (increment)
                $update_sql = "UPDATE {$target_db}.events SET number_of_tickets = number_of_tickets + ? WHERE id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$tickets_to_return, $event_id]);

                $_SESSION['message'] = ['type' => 'success', 'text' => 'Booking ID ' . $booking_id . ' cancelled successfully. Tickets returned to pool.'];
                $pdo->commit();
            }

        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Unknown action requested.'];
        }

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); } // Rollback on error
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Database error during booking process. Please try again.'];
        error_log("Booking Error: " . $e->getMessage());

    } finally {
        if ($pdo) { close_db_connection($pdo); }
    }

    header('Location: ' . $redirect_url);
    exit;
?>