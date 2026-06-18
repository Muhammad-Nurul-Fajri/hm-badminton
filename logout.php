<?php
/**
 * FILE: logout.php
 * FUNGSI: Logout pelanggan
 */

require_once 'config.php';
session_destroy();
redirect('index.php');
?>