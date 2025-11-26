<?php
    // footer.php (Minimal CSS remains)
    $current_year = date("Y"); 
?>

<link rel="stylesheet" href="footer_style.css">

<footer class="footer-bar">
    <div class="footer-links">
        <a href="#">About Us</a> |
        <a href="#">Contact</a> |
        <a href="#">Privacy Policy</a> |
        <a href="#">Terms of Service</a>
    </div>
    <div class="copyright">
        &copy; <?php echo $current_year; ?> Event Manager Pro. All Rights Reserved.
    </div>
</footer>