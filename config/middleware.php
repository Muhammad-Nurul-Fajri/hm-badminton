<?php
/**
 * FILE: config/middleware.php
 * FUNGSI: Auth guard functions untuk proteksi halaman
 */

require_once __DIR__ . '/functions.php';

function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('Silakan login terlebih dahulu!', 'error');
        redirect('auth/login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        setFlash('Anda tidak memiliki akses ke halaman ini!', 'error');
        switch ($_SESSION['role']) {
            case 'admin': redirect('admin/dashboard.php'); break;
            case 'owner': redirect('owner/dashboard.php'); break;
            default: redirect('pelanggan/dashboard.php'); break;
        }
    }
}

function requireAdmin() {
    requireRole('admin');
}

function requireOwner() {
    requireRole('owner');
}

function requireCustomer() {
    requireRole('user');
}

/**
 * Get admin's branch ID from session
 */
function getAdminCabangId() {
    return $_SESSION['cabang_id'] ?? null;
}
?>
