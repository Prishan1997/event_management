<?php
    // dashboard.php - User Event Dashboard
    session_start();

    // --- 1. DB Connection and Setup ---
    require 'db_con.php'; 
    $target_db = 'event_management'; 

    // --- 2. PROTECTION CHECK ---
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
        $_SESSION['login_error'] = 'Access denied. Please log in.';
        header('Location: login.php');
        exit;
    }

    $current_user = $_SESSION['username'] ?? 'Guest';
    $user_id = $_SESSION['user_id'] ?? null; // Assume user_id is stored in the session
    
    // --- 3. DATA FETCHING ---
    $all_available_events = []; 
    $booked_events = [];
    $db_error_message = null;
    
    $pdo = null;
    try {
        $pdo = open_db_connection();
        
        // --- Fetch User's Booked Events with Event Status ---
        if ($user_id) {
            $booking_sql = "
                SELECT
                    b.id AS booking_id,
                    b.tickets_booked,
                    e.event_name,
                    e.date,
                    e.status AS event_status  -- Retrieve event status from the events table
                FROM
                    {$target_db}.bookings b
                JOIN
                    {$target_db}.events e ON b.event_id = e.id
                WHERE
                    b.user_id = :user_id
                ORDER BY
                    e.date ASC
            ";
            $booking_stmt = $pdo->prepare($booking_sql);
            $booking_stmt->execute([':user_id' => $user_id]);
            $booked_events = $booking_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // --- Fetch All Active Events for the Booking Modal ---
        // Only allow booking for 'Active' events
        $event_sql = "SELECT id, event_name, date, number_of_tickets FROM {$target_db}.events WHERE status = 'Active' ORDER BY date ASC";
        $event_stmt = $pdo->query($event_sql);
        $all_available_events = $event_stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (\PDOException $e) {
        error_log("DB Fetch Error on User Dashboard: " . $e->getMessage());
        $db_error_message = "Could not load data: Database error occurred.";
    } finally {
        if ($pdo) { close_db_connection($pdo); }
    }
    
    $booked_events_count = count($booked_events);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Event Manager</title>
    <link rel="stylesheet" href="dashboard_style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="scrollable-content-wrapper"> 
        
        <div class="dashboard-container">
            <h1>Event Management Dashboard</h1>        
            
            <?php if (isset($db_error_message)): ?>
                <p style="color: red; font-weight: bold; padding: 10px; border: 1px solid red; border-radius: 4px;">
                    <?php echo htmlspecialchars($db_error_message); ?>
                </p>
            <?php endif; ?>

            <div class="tabs" style="display: flex; border-bottom: 2px solid #ddd; margin-bottom: 20px;">
                <button style="padding: 10px 15px; background-color: #f4f4f4; border: 1px solid #ccc; border-bottom: none; border-top-left-radius: 6px; border-top-right-radius: 6px; cursor: pointer; font-weight: bold;">
                    Booked Events (<?php echo $booked_events_count; ?>)
                </button>
            </div>
            
            <div class="quick-actions" style="margin-bottom: 25px; text-align: right;">
                <a href="#bookModal" id="openModalBtn" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    + Book New Tickets
                </a>
            </div>
            
            <div id="booked-events-content">
                <h2>My Booked Events</h2>
                <?php if (empty($booked_events)): ?>
                    <p>You have no upcoming events booked.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr style="background-color: #eee;">
                                <th>Event</th>
                                <th style="text-align: center;">Date</th>
                                <th style="text-align: center;">Tickets</th>
                                <th style="text-align: center;">Status</th> <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($booked_events as $booking): 
                                $status = htmlspecialchars($booking['event_status']);
                                $status_class = 'status-' . $status;
                                // Only allow cancellation if the event is Active
                                $can_cancel = $status === 'Active'; 
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['event_name']); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($booking['date']); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($booking['tickets_booked']); ?></td>
                                <td style="text-align: center;">
                                    <span class="<?php echo $status_class; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($can_cancel): ?>
                                        <form action="process_booking.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="cancel_user_booking">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <button type="submit" name="cancel_booking" style="background-color: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                                ❌ Cancel Booking
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="action-disabled">Cannot Cancel (Event <?php echo $status; ?>)</span>
                                    <?php endif; ?>
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
    
    <div id="bookModal" class="modal-overlay">
        <div class="modal-content">
            <a href="#" class="close-btn" onclick="document.getElementById('bookModal').style.display='none'; return false;">&times;</a>
            <h2>Book New Tickets</h2>
            <p>Select an event and the number of tickets you wish to purchase.</p>
            
            <form action="process_booking.php" method="POST">
                <input type="hidden" name="action" value="new_booking">
                <label for="event_select">Select Event:</label>
                <select id="event_select" name="event_id" required>
                    <?php if (!empty($all_available_events)): ?>
                        <?php foreach ($all_available_events as $event): ?>
                            <option value="<?php echo htmlspecialchars($event['id']); ?>">
                                <?php echo htmlspecialchars($event['event_name']); ?> (<?php echo htmlspecialchars($event['date']); ?>) - Tickets Available: <?php echo htmlspecialchars($event['number_of_tickets']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                         <option value="" disabled>No events currently available for booking</option>
                    <?php endif; ?>
                </select>
                
                <label for="ticket_count">Number of Tickets:</label>
                <input type="number" id="ticket_count" name="tickets" min="1" value="1" required>
                <button type="submit">Book Now</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('openModalBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('bookModal').style.display = 'flex';
        });
        
        const bookModal = document.getElementById('bookModal');
        if (bookModal) {
            bookModal.addEventListener('click', function(e) {
                if (e.target === bookModal) {
                    bookModal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>