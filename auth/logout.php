<?php
/**
 * FILE: auth/logout.php
 * FUNGSI: Logout dan destroy session
 */

require_once __DIR__ . '/../config/config.php';
session_destroy();
header("Location: " . BASE_URL . "auth/login.php");
exit;
?>
