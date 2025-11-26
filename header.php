<?php
    // header.php
    session_start();
    
    // 1. Define necessary variables
    $display_username = $_SESSION['username'] ?? 'User';
    $is_admin = $_SESSION['is_admin'] ?? FALSE; 
    
    // 2. Define links (direct file names since all files are in the root)
    $dashboard_link = 'dashboard.php';
    $admin_link = 'admin_dashboard.php';
    $logout_link = 'logout.php'; 

    // 3. PHP Logic for Active Tab Highlighting
    // Get the current page file name (e.g., "dashboard.php")
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Set active class variables
    $dashboard_active = ($current_page === 'dashboard.php') ? 'active' : '';
    $admin_active = ($current_page === 'admin_dashboard.php') ? 'active' : '';
?>

<link rel="stylesheet" href="header_style.css">

<style>
    /* --- REVISED INLINE STYLES --- */
    .header-bar {
        display: flex;
        /* CHANGE 1: Use flex-start to push items to the left, but we will 
           revert this later to space-between and control the nav directly. 
           We will use space-between to keep the title and user menu separate. */
        justify-content: space-between; 
        align-items: center;
        padding: 10px 20px;
        background-color: #343a40; 
        color: white;
    }
    
    /* NEW: Container to group the Title and the Tabs */
    .header-left {
        display: flex;
        align-items: center;
        gap: 25px; /* Space between title and navigation */
    }

    /* Style for the new navigation bar */
    .main-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
    }
    .main-nav ul li {
        margin: 0 10px;
    }
    .main-nav ul li a {
        color: white;
        text-decoration: none;
        padding: 8px 15px;
        border-radius: 4px;
        transition: background-color 0.2s;
        font-weight: 500;
    }
    .main-nav ul li a:hover {
        background-color: #495057;
    }
    
    /* CHANGE 2: Styling for the active tab */
    .main-nav ul li a.active {
        background-color: #007bff; /* Highlight color for active tab */
        font-weight: bold;
    }

    /* Adjust the user menu to align properly */
    .user-menu {
        position: relative;
        cursor: pointer;
        z-index: 10;
        padding: 8px 15px; 
    }
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: #f9f9f9;
        min-width: 120px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    }
    .dropdown-content a {
        color: #333;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
    }
    .user-menu:hover .dropdown-content {
        display: block;
    }
</style>

<div class="header-bar">
    
    <div class="header-left">
        <div class="header-title">Event Manager Pro</div>

        <nav class="main-nav">
            <ul>
                <li><a href="<?php echo $dashboard_link; ?>" class="<?php echo $dashboard_active; ?>">My Bookings</a></li>

                <?php if ($is_admin): ?>
                    <li><a href="<?php echo $admin_link; ?>" class="<?php echo $admin_active; ?>">Admin Panel</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        </div>

    <div class="user-menu">
        <div class="user-name-display">
            Welcome, <?php echo htmlspecialchars($display_username); ?> 
            <span style="font-size: 0.8em;">▼</span> 
        </div>
        <div class="dropdown-content">
            <a href="<?php echo $logout_link; ?>">Sign Out</a>
        </div>
    </div>
</div>