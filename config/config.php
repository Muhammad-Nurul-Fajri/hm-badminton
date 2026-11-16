<?php
/**
 * FILE: config/config.php
 * FUNGSI: Konfigurasi database, session, base URL, helper dasar
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
date_default_timezone_set('Asia/Jakarta');

// Base URL & Path
define('BASE_URL', 'http://localhost/hm-badminton/');
define('BASE_PATH', dirname(__DIR__) . '/');

// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hm_badminton');

/**
 * Get PDO database connection (singleton)
 */
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            // Ensure extended equipment inventory database tables exist
            $db->exec("CREATE TABLE IF NOT EXISTS equipment_types (
                equipment_type_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                category ENUM('Racket', 'Shuttlecock', 'Shoes', 'Jersey', 'Sports Bag', 'Grip', 'Net', 'Training Cone', 'Wristband', 'Towel', 'Other') NOT NULL DEFAULT 'Racket',
                rental_price INT NOT NULL,
                description TEXT DEFAULT NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS equipment (
                equipment_id INT AUTO_INCREMENT PRIMARY KEY,
                equipment_type_id INT NOT NULL,
                cabang_id INT NOT NULL,
                equipment_name VARCHAR(150) NOT NULL,
                brand VARCHAR(100) NOT NULL,
                model VARCHAR(100) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                rental_price_per_session INT NOT NULL,
                stock_quantity INT NOT NULL DEFAULT 0,
                available_stock INT NOT NULL DEFAULT 0,
                rented_stock INT NOT NULL DEFAULT 0,
                damaged_stock INT NOT NULL DEFAULT 0,
                equipment_condition ENUM('New', 'Good', 'Fair', 'Damaged') NOT NULL DEFAULT 'Good',
                equipment_status ENUM('Available', 'Unavailable', 'Maintenance') NOT NULL DEFAULT 'Available',
                image_url VARCHAR(255) DEFAULT NULL,
                is_deleted TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(equipment_type_id) ON DELETE RESTRICT,
                FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE CASCADE,
                INDEX idx_cabang_status (cabang_id, equipment_status),
                INDEX idx_search (equipment_name, brand, model)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS inventory (
                inventory_id INT AUTO_INCREMENT PRIMARY KEY,
                cabang_id INT NOT NULL,
                equipment_type_id INT NOT NULL,
                stock_quantity INT NOT NULL DEFAULT 0,
                rented_quantity INT NOT NULL DEFAULT 0,
                damaged_quantity INT NOT NULL DEFAULT 0,
                item_condition ENUM('good', 'fair', 'damaged') NOT NULL DEFAULT 'good',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE CASCADE,
                FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(equipment_type_id) ON DELETE RESTRICT,
                UNIQUE KEY uk_cabang_equipment (cabang_id, equipment_type_id),
                INDEX idx_branch_inventory (cabang_id, equipment_type_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS rental_transactions (
                rental_id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                user_id INT NOT NULL,
                rental_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                return_date TIMESTAMP NULL DEFAULT NULL,
                total_rental_fee INT NOT NULL DEFAULT 0,
                payment_status ENUM('unpaid', 'dp_paid', 'paid') NOT NULL DEFAULT 'unpaid',
                rental_status ENUM('pending', 'rented', 'returned', 'cancelled') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES reservasi(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_booking (booking_id),
                INDEX idx_user (user_id),
                INDEX idx_rental_status (rental_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS rental_transaction_details (
                detail_id INT AUTO_INCREMENT PRIMARY KEY,
                rental_id INT NOT NULL,
                equipment_id INT NOT NULL,
                quantity INT NOT NULL,
                unit_price INT NOT NULL,
                subtotal INT NOT NULL,
                FOREIGN KEY (rental_id) REFERENCES rental_transactions(rental_id) ON DELETE CASCADE,
                FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE RESTRICT,
                INDEX idx_rental (rental_id),
                INDEX idx_equipment (equipment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS rental_details (
                detail_id INT AUTO_INCREMENT PRIMARY KEY,
                rental_id INT NOT NULL,
                inventory_id INT NOT NULL,
                quantity INT NOT NULL,
                unit_price INT NOT NULL,
                subtotal INT NOT NULL,
                FOREIGN KEY (rental_id) REFERENCES rental_transactions(rental_id) ON DELETE CASCADE,
                FOREIGN KEY (inventory_id) REFERENCES inventory(inventory_id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS equipment_returns (
                return_id INT AUTO_INCREMENT PRIMARY KEY,
                rental_id INT NOT NULL,
                equipment_id INT NOT NULL,
                admin_id INT NULL DEFAULT NULL,
                return_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                quantity_returned INT NOT NULL,
                condition_on_return ENUM('Good', 'Fair', 'Damaged') NOT NULL DEFAULT 'Good',
                notes TEXT DEFAULT NULL,
                FOREIGN KEY (rental_id) REFERENCES rental_transactions(rental_id) ON DELETE CASCADE,
                FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE RESTRICT,
                FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_return_rental (rental_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS equipment_maintenance (
                maintenance_id INT AUTO_INCREMENT PRIMARY KEY,
                equipment_id INT NOT NULL,
                admin_id INT NOT NULL,
                start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                end_date TIMESTAMP NULL DEFAULT NULL,
                status ENUM('In_Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'In_Progress',
                notes TEXT DEFAULT NULL,
                FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE,
                FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_maintenance_equip (equipment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            die("Terjadi kesalahan pada sistem. Silakan coba lagi nanti.");
        }
    }
    return $db;
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Cek apakah user adalah admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Cek apakah user adalah owner
 */
function isOwner() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'owner';
}

/**
 * Redirect ke URL (relative terhadap BASE_URL)
 */
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

/**
 * Set flash message ke session
 */
function setFlash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Ambil dan hapus flash message dari session
 */
function flashMessage() {
    if (isset($_SESSION['flash'])) {
        $msg = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $msg;
    }
    return null;
}
?>
