<?php
require_once __DIR__ . '/config/config.php';
$db = getDB();
try {
    $db->exec("SET GLOBAL event_scheduler = ON;");
    $db->exec("DROP EVENT IF EXISTS evt_auto_conclude_reservations;");
    $db->exec("CREATE EVENT evt_auto_conclude_reservations
    ON SCHEDULE EVERY 1 MINUTE
    DO
    BEGIN
        UPDATE equipment e
        JOIN rental_transaction_details rtd ON e.equipment_id = rtd.equipment_id
        JOIN rental_transactions rt ON rtd.rental_id = rt.rental_id
        JOIN reservasi r ON rt.booking_id = r.id
        SET e.rented_stock = GREATEST(0, e.rented_stock - rtd.quantity),
            e.available_stock = LEAST(e.stock_quantity - e.damaged_stock, e.available_stock + rtd.quantity)
        WHERE rt.rental_status = 'rented'
          AND (r.tanggal < CURDATE() OR (r.tanggal = CURDATE() AND r.waktu_selesai <= CURTIME()));

        UPDATE rental_transactions rt
        JOIN reservasi r ON rt.booking_id = r.id
        SET rt.rental_status = 'returned',
            rt.return_date = NOW()
        WHERE rt.rental_status = 'rented'
          AND (r.tanggal < CURDATE() OR (r.tanggal = CURDATE() AND r.waktu_selesai <= CURTIME()));

        UPDATE reservasi
        SET status = 'completed',
            updated_at = NOW()
        WHERE status = 'confirmed'
          AND (tanggal < CURDATE() OR (tanggal = CURDATE() AND waktu_selesai <= CURTIME()));
    END;");
    echo "MySQL Event created successfully!\n";
} catch (Exception $e) {
    echo "Event creation error: " . $e->getMessage() . "\n";
}
