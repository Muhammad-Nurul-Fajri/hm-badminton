<?php
/**
 * FILE: index.php (ROOT)
 * FUNGSI: Entry point — redirect ke halaman login
 */

require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    switch ($_SESSION['role'] ?? 'user') {
        case 'admin': redirect('admin/dashboard.php'); break;
        case 'owner': redirect('owner/dashboard.php'); break;
        default: redirect('pelanggan/dashboard.php'); break;
    }
} else {
    redirect('auth/login.php');
}
?>