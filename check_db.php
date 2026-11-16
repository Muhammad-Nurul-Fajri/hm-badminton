<?php
require_once __DIR__ . '/config/config.php';
$db = getDB();
$tables = ['cabang', 'lapangan', 'users', 'reservasi', 'payments', 'equipment_types', 'equipment', 'inventory', 'rental_transactions', 'rental_details', 'rental_transaction_details', 'equipment_returns'];
foreach ($tables as $t) {
    try {
        $c = $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "$t: $c\n";
    } catch (Exception $e) {
        echo "$t: error - " . $e->getMessage() . "\n";
    }
}
