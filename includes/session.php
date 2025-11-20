<?php
// Configure session settings before starting the session
ini_set('session.cookie_lifetime', 3600);
ini_set('session.gc_maxlifetime', 3600);

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
